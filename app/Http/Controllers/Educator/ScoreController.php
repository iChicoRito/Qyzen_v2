<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportScoresBulkRequest;
use App\Http\Requests\GrantRetakeRequest;
use App\Models\Assessment;
use App\Models\Score;
use App\Models\StudentAssessmentRetake;
use App\Services\NotificationService;
use App\Services\ScoreExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// G7: scores are READ-ONLY for educators (they grade nothing manually — grading is server-side
// at submit, Stage H6). Educator may grant retakes and view per-attempt detail. G8: export.
class ScoreController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private ScoreExportService $exporter,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Score::class);

        // Task 26: eager-load student avatar + subject/section/term for the columns and the
        // client-side subject/section/term filters. Default view sorted A→Z by surname (newest
        // submission as the tiebreak). Filtering stays client-side (KTDataTable), like the rest.
        $scores = Score::visibleTo(Auth::user())
            ->with([
                'student:id,given_name,surname,user_id,profile_picture',
                'assessment:id,assessment_code,term',
                'assessment.academicTerm:id,term_name',
                'subject:id,subject_code,subject_name',
                'section:id,section_name',
            ])
            ->orderByDesc('submitted_at')->get()
            ->sortBy(fn ($s) => mb_strtolower(optional($s->student)->surname ?? ''))
            ->values();

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

        return view('educator.scores.index', compact('scores', 'exportOptions'));
    }

    public function show(Score $score): View
    {
        $this->authorize('view', $score);

        // Attempt detail: per-question correct answer + student answer + isCorrect.
        // correct_answer is loaded SERVER-SIDE here (educator view) — never serialized to a student.
        $score->load(['student:id,given_name,surname,user_id', 'assessment.quizzes']);

        return view('educator.scores.show', compact('score'));
    }

    public function grantRetake(GrantRetakeRequest $request): RedirectResponse
    {
        $this->authorize('create', \App\Models\Assessment::class);

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
