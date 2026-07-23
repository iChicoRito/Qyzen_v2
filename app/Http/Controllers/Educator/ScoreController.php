<?php

namespace App\Http\Controllers\Educator;

use App\Exports\ScoreUploadTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportScoresBulkRequest;
use App\Http\Requests\GrantRetakeRequest;
use App\Imports\OfflineScoresImport;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\Score;
use App\Models\Section;
use App\Models\StudentAssessmentRetake;
use App\Models\Subject;
use App\Services\NotificationService;
use App\Services\OfflineScoreUploadService;
use App\Services\ScoreExportService;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// G7: scores are READ-ONLY for educators (they grade nothing manually — grading is server-side
// at submit, Stage H6). Educator may grant retakes and view per-attempt detail. G8: export.
class ScoreController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private ScoreExportService $exporter,
        private OfflineScoreUploadService $offlineUploads,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Score::class);

        // Task 46: eager-load the visible score rows, then apply GET-backed search, filters,
        // sorting, and pagination before the shared table renders.
        $query = Score::query()
            ->where('tbl_scores.educator_id', Auth::id())
            ->whereIn('tbl_scores.status', ['submitted', 'passed', 'failed'])
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('tbl_scores as newer')
                    ->whereColumn('newer.educator_id', 'tbl_scores.educator_id')
                    ->whereColumn('newer.student_id', 'tbl_scores.student_id')
                    ->whereColumn('newer.assessment_id', 'tbl_scores.assessment_id')
                    ->whereNull('newer.deleted_at')
                    ->whereIn('newer.status', ['submitted', 'passed', 'failed'])
                    ->where(function ($newer) {
                        $newer->whereColumn('newer.submitted_at', '>', 'tbl_scores.submitted_at')
                            ->orWhere(function ($tie) {
                                $tie->whereColumn('newer.submitted_at', '=', 'tbl_scores.submitted_at')
                                    ->whereColumn('newer.id', '>', 'tbl_scores.id');
                            });
                    });
            })
            ->select('tbl_scores.*')
            ->leftJoin('tbl_users as sort_students', 'sort_students.id', '=', 'tbl_scores.student_id')
            ->leftJoin('tbl_assessments as sort_assessments', 'sort_assessments.id', '=', 'tbl_scores.assessment_id')
            ->leftJoin('tbl_subjects as sort_subjects', 'sort_subjects.id', '=', 'tbl_scores.subject_id')
            ->leftJoin('tbl_sections as sort_sections', 'sort_sections.id', '=', 'tbl_scores.section_id')
            ->leftJoin('tbl_academic_term as sort_terms', 'sort_terms.id', '=', 'sort_assessments.term')
            ->with([
                'student:id,given_name,surname,user_id,profile_picture',
                'assessment:id,assessment_code,term',
                'assessment.academicTerm:id,term_name',
                'subject:id,subject_code,subject_name',
                'section:id,section_name',
            ]);
        TableQuery::search($query, $request->query('search'), [
            fn (Builder $q, string $term) => $q->orWhereHas('student', fn ($s) => $s
                ->where('given_name', 'like', "%{$term}%")
                ->orWhere('surname', 'like', "%{$term}%")
                ->orWhere('user_id', 'like', "%{$term}%")),
            fn (Builder $q, string $term) => $q->orWhereHas('assessment', fn ($a) => $a->where('assessment_code', 'like', "%{$term}%")),
            fn (Builder $q, string $term) => $q->orWhereHas('subject', fn ($s) => $s
                ->where('subject_code', 'like', "%{$term}%")
                ->orWhere('subject_name', 'like', "%{$term}%")),
            fn (Builder $q, string $term) => $q->orWhereHas('section', fn ($s) => $s->where('section_name', 'like', "%{$term}%")),
        ]);
        TableQuery::filters($query, $request, [
            'assessment' => fn (Builder $q, string $value) => $q->whereHas('assessment', fn ($a) => $a->where('assessment_code', $value)),
            'subject' => 'tbl_scores.subject_id',
            'section' => 'tbl_scores.section_id',
            'term' => fn (Builder $q, string $value) => $q->whereHas('assessment', fn ($a) => $a->where('term', $value)),
            'result' => fn (Builder $q, string $value) => $q->where('is_passed', $value === 'passed'),
        ]);
        // One latest submitted row per student/assessment, while correlated aggregates retain the
        // full attempt history needed by the concise table and existing detail route.
        $query->selectRaw("(select count(*) from tbl_scores as attempts where attempts.educator_id = tbl_scores.educator_id and attempts.student_id = tbl_scores.student_id and attempts.assessment_id = tbl_scores.assessment_id and attempts.deleted_at is null and attempts.status in ('submitted', 'passed', 'failed')) as attempts_count")
            ->selectRaw("(select best.score from tbl_scores as best where best.educator_id = tbl_scores.educator_id and best.student_id = tbl_scores.student_id and best.assessment_id = tbl_scores.assessment_id and best.deleted_at is null and best.status in ('submitted', 'passed', 'failed') order by case when best.total_questions > 0 then best.score * 1.0 / best.total_questions else 0 end desc, best.id desc limit 1) as best_score")
            ->selectRaw("(select best.total_questions from tbl_scores as best where best.educator_id = tbl_scores.educator_id and best.student_id = tbl_scores.student_id and best.assessment_id = tbl_scores.assessment_id and best.deleted_at is null and best.status in ('submitted', 'passed', 'failed') order by case when best.total_questions > 0 then best.score * 1.0 / best.total_questions else 0 end desc, best.id desc limit 1) as best_total_questions")
            ->selectRaw("(select case when best.total_questions > 0 then best.score * 100.0 / best.total_questions else 0 end from tbl_scores as best where best.educator_id = tbl_scores.educator_id and best.student_id = tbl_scores.student_id and best.assessment_id = tbl_scores.assessment_id and best.deleted_at is null and best.status in ('submitted', 'passed', 'failed') order by case when best.total_questions > 0 then best.score * 1.0 / best.total_questions else 0 end desc, best.id desc limit 1) as best_percentage");
        TableQuery::sort($query, $request, [
            'student' => function (Builder $q, string $direction): void {
                $q->orderBy('sort_students.surname', $direction)
                    ->orderBy('sort_students.given_name', $direction)
                    ->orderBy('sort_students.user_id', $direction)
                    ->orderBy('tbl_scores.id', 'desc');
            },
            'assessment' => 'sort_assessments.assessment_code',
            'subject' => function (Builder $q, string $direction): void {
                $q->orderBy('sort_subjects.subject_code', $direction)
                    ->orderBy('sort_subjects.subject_name', $direction)
                    ->orderBy('tbl_scores.id', 'desc');
            },
            'section' => 'sort_sections.section_name',
            'term' => 'sort_terms.term_name',
            'score' => 'best_percentage',
            'attempts' => 'attempts_count',
            'result' => 'is_passed',
            'submitted' => 'submitted_at',
        ], 'submitted', 'desc');

        $scores = $query->paginate(TableQuery::perPage($request))->withQueryString();

        // Task 27: this educator's assessments, flattened for the export modal's cascading
        // Subject → Section → Assessment selects — rendered inline so opening the modal needs
        // no extra round trip.
        $exportOptions = Assessment::visibleTo(Auth::user())
            ->with([
                'subject:id,subject_code,subject_name',
                'section:id,section_name',
                'academicTerm:id,term_name,semester,academic_year_id',
                'academicTerm.year:id,year',
            ])
            ->get()
            ->map(fn (Assessment $a) => [
                'uuid' => $a->uuid,
                'assessmentCode' => $a->assessment_code,
                'subjectId' => $a->subject_id,
                'subjectLabel' => trim(($a->subject?->subject_code ?? '').' — '.($a->subject?->subject_name ?? ''), ' —'),
                'sectionId' => $a->section_id,
                'sectionLabel' => $a->section?->section_name,
                'termId' => $a->term,
                'termLabel' => $a->academicTerm?->term_name,
                'semester' => $a->academicTerm?->semester,
                'academicYear' => $a->academicTerm?->year?->year,
            ])
            ->values();

        $selectedSection = $request->query('section');
        $selectedSubject = $request->query('subject');
        $filterAssessments = Assessment::visibleTo(Auth::user())
            ->when($selectedSection, fn ($q) => $q->where('section_id', $selectedSection))
            ->when($selectedSubject, fn ($q) => $q->where('subject_id', $selectedSubject))
            ->orderBy('assessment_code')->pluck('assessment_code');
        $filterSubjects = Subject::visibleTo(Auth::user())
            ->when($selectedSection, fn ($q) => $q->where('sections_id', $selectedSection))
            ->orderBy('subject_code')->get(['id', 'subject_code', 'subject_name']);
        $filterSections = Section::visibleTo(Auth::user())->orderBy('section_name')->get(['id', 'section_name']);
        $filterTerms = AcademicTerm::orderBy('term_name')->get(['id', 'term_name']);

        return view('educator.scores.index', compact('scores', 'exportOptions', 'filterAssessments', 'filterSubjects', 'filterSections', 'filterTerms'));
    }

    public function destroy(Request $request, Score $score): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $score);
        abort_unless(! $score->trashed(), 404);
        $score->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Score moved to Deleted Scores.',
                'restore_url' => route('educator.scores.restore', $score, false),
            ]);
        }

        return redirect()->route('educator.scores.index')->with('status', 'Score deleted.');
    }

    public function deleted(Request $request): View
    {
        $this->authorize('viewAny', Score::class);

        $scores = Score::onlyTrashed()
            ->where('educator_id', Auth::id())
            ->with([
                'student:id,given_name,surname,user_id',
                'assessment:id,assessment_code',
                'subject:id,subject_code,subject_name',
                'section:id,section_name',
            ])
            ->latest('deleted_at')
            ->paginate(TableQuery::perPage($request))
            ->withQueryString();

        return view('educator.scores.deleted', compact('scores'));
    }

    public function restore(Request $request, Score $score): JsonResponse|RedirectResponse
    {
        abort_unless($score->trashed(), 404);
        $this->authorize('restore', $score);
        $score->restore();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Score restored.',
            ]);
        }

        return redirect()->route('educator.scores.deleted')->with('status', 'Score restored.');
    }

    public function show(Score $score): View
    {
        $this->authorize('view', $score);

        // Attempt detail: per-question correct answer + student answer + isCorrect.
        // correct_answer is loaded SERVER-SIDE here (educator view) — never serialized to a student.
        // Only this attempt's pinned drawn subset — not the assessment's full eligible pool.
        $score->load(['student:id,given_name,surname,user_id']);
        // withTrashed: a question deleted from the bank after this attempt must still resolve
        // here so historical per-question review stays whole (Task 13).
        $reviewQuestions = Quiz::withTrashed()->whereIn('id', $score->drawn_quiz_ids ?? [])->get();

        return view('educator.scores.show', compact('score', 'reviewQuestions'));
    }

    public function grantRetake(GrantRetakeRequest $request): RedirectResponse
    {
        $this->authorize('create', Assessment::class);

        $data = $request->validated();
        StudentAssessmentRetake::updateOrCreate(
            ['educator_id' => Auth::id(), 'student_id' => $data['student_id'], 'assessment_id' => $data['assessment_id']],
            ['extra_retake_count' => $data['extra_retake_count'], 'is_active' => true],
        );

        $this->notifications->emit(Auth::user(), 'retake_updated', (int) $data['student_id'], [
            'assessment_id' => (int) $data['assessment_id'], 'title' => 'Retake granted',
            'subject_id' => Assessment::find($data['assessment_id'])?->subject_id,
            'link_path' => route('student.assessments.index'),
        ]);

        return back()->with('status', 'Retake granted.');
    }

    public function uploadTemplate()
    {
        $this->authorize('import', Score::class);

        return Excel::download(new ScoreUploadTemplateExport, 'offline-score-upload-template.xlsx');
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->authorize('import', Score::class);

        $request->validate([
            'term_id' => ['required', 'integer', 'exists:tbl_academic_term,id'],
            'assessment_uuid' => ['required', 'uuid'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $assessment = Assessment::visibleTo($request->user())
            ->where('uuid', $request->input('assessment_uuid'))
            ->firstOrFail();

        if ((int) $assessment->term !== (int) $request->input('term_id')) {
            throw ValidationException::withMessages([
                'assessment_uuid' => ['The selected assessment does not belong to the selected term.'],
            ]);
        }

        $import = new OfflineScoresImport;
        Excel::import($import, $request->file('file'));

        $created = $this->offlineUploads->import($request->user(), $assessment, $import->rows);

        return redirect()->route('educator.scores.index')->with('status', "Uploaded {$created} offline score(s).");
    }

    // Task 27: preview counts (enrolled/with-submission/without) shown before export.
    public function exportPreview(Assessment $assessment): JsonResponse
    {
        $this->authorize('view', $assessment);

        return response()->json($this->exporter->preview($assessment));
    }

    // G8/Task 27: single-assessment xlsx export — roster-complete, best-attempt resolved, styled.
    public function export(Assessment $assessment)
    {
        $this->authorize('view', $assessment);

        $book = $this->exporter->single($assessment);

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, "scores-{$assessment->assessment_code}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // G8/Task 27: bulk export → zip of TERM/SUBJECT/SECTION.xlsx. type = all|term|semester.
    public function exportBulk(ExportScoresBulkRequest $request)
    {
        $this->authorize('viewAny', Score::class);

        return $this->exporter->bulk(Auth::user(), $request->validated());
    }
}
