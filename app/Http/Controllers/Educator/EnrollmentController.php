<?php

namespace App\Http\Controllers\Educator;

use App\Exports\EnrollmentImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Jobs\ProcessEnrollmentImport;
use App\Models\Enrolled;
use App\Models\EnrollmentImport;
use App\Models\Subject;
use App\Models\User;
use App\Services\EnrollmentExportService;
use App\Services\NotificationService;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

// G4: enrollment. Single + bulk xlsx, deduped, with enrollment_created/updated/deleted
// notifications to students only (best-effort via NotificationService).
class EnrollmentController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private EnrollmentExportService $exports,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Enrolled::class);

        // One row per subject/section (grouped), not per enrollment. Students live on the detail page.
        $query = Subject::query()
            ->where('tbl_subjects.educator_id', Auth::id())
            ->has('enrollments')
            ->with('section:id,section_name')
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => fn ($q) => $q->where('is_active', true),
            ]);
        TableQuery::search($query, $request->query('search'), [
            'subject_code',
            'subject_name',
            fn (Builder $q, string $term) => $q->orWhereHas('section', fn ($s) => $s->where('section_name', 'like', "%{$term}%")),
        ]);
        TableQuery::sort($query, $request, [
            'subject' => 'subject_code',
            'section' => function (Builder $q, string $direction): void {
                $q->leftJoin('tbl_sections as sort_sections', 'sort_sections.id', '=', 'tbl_subjects.sections_id')
                    ->select('tbl_subjects.*')
                    ->orderBy('sort_sections.section_name', $direction)
                    ->orderBy('tbl_subjects.id', 'desc');
            },
            'enrolled' => 'enrollments_count',
        ], 'subject');

        $subjects = $query->paginate(TableQuery::perPage($request))->withQueryString();

        $imports = EnrollmentImport::ownedBy(Auth::user())->latest()->take(6)->get();

        return view('educator.enrollment.index', compact('subjects', 'imports'));
    }

    // Detail page: all students enrolled in one subject/section (mirrors scores.show).
    public function showSubject(Request $request, Subject $subject): View
    {
        $this->authorize('viewAny', Enrolled::class);
        abort_unless(Subject::visibleTo(Auth::user())->whereKey($subject->getKey())->exists(), 403);

        $subject->load('section:id,section_name');
        $query = Enrolled::query()
            ->where('tbl_enrolled.subject_id', $subject->id)
            ->where('tbl_enrolled.educator_id', Auth::id())
            ->with('student:id,given_name,surname,user_id,profile_picture');
        TableQuery::search($query, $request->query('search'), [
            fn (Builder $q, string $term) => $q->orWhereHas('student', fn ($s) => $s
                ->where('given_name', 'like', "%{$term}%")
                ->orWhere('surname', 'like', "%{$term}%")
                ->orWhere('user_id', 'like', "%{$term}%")),
        ]);
        TableQuery::filters($query, $request, ['status' => 'is_active']);
        TableQuery::sort($query, $request, [
            'student_no' => 'user_id',
            'surname' => function (Builder $q, string $direction): void {
                $q->leftJoin('tbl_users as sort_students', 'sort_students.id', '=', 'tbl_enrolled.student_id')
                    ->select('tbl_enrolled.*')
                    ->orderBy('sort_students.surname', $direction)
                    ->orderBy('sort_students.given_name', $direction)
                    ->orderBy('tbl_enrolled.id', 'desc');
            },
            'given_name' => function (Builder $q, string $direction): void {
                $q->leftJoin('tbl_users as sort_students', 'sort_students.id', '=', 'tbl_enrolled.student_id')
                    ->select('tbl_enrolled.*')
                    ->orderBy('sort_students.given_name', $direction)
                    ->orderBy('sort_students.surname', $direction)
                    ->orderBy('tbl_enrolled.id', 'desc');
            },
            'status' => 'is_active',
            'id' => 'id',
        ], 'id', 'desc');

        $enrollments = $query->paginate(TableQuery::perPage($request))->withQueryString();

        return view('educator.enrollment.show', compact('subject', 'enrollments'));
    }

    // Task 01: bulk-remove every student enrolled in one subject/section.
    public function unenrollAllForSubject(Subject $subject): RedirectResponse
    {
        $this->authorize('viewAny', Enrolled::class);
        abort_unless(Subject::visibleTo(Auth::user())->whereKey($subject->getKey())->exists(), 403);

        $rows = Enrolled::where('educator_id', Auth::id())->where('subject_id', $subject->id)->get();
        $studentIds = $rows->pluck('student_id')->all();
        Enrolled::whereKey($rows->pluck('id'))->delete();

        if ($studentIds) {
            $this->notifications->emitToMany(Auth::user(), 'enrollment_deleted', $studentIds, [
                'subject_id' => $subject->id,
                'title' => 'Unenrolled from a subject',
                'link_path' => route('student.assessments.index'),
            ]);
        }

        return redirect()->route('educator.enrollment.index')
            ->with('status', count($studentIds).' student(s) unenrolled.');
    }

    // Task 43: profile card fragment for one enrolled student, opened in the shared modal.
    // Gated by enrollment ownership — an educator only sees students they actually teach.
    public function showStudent(User $user): View
    {
        $this->authorize('viewAny', Enrolled::class);
        abort_unless(
            Enrolled::where('student_id', $user->id)->visibleTo(Auth::user())->exists(),
            403
        );

        $user->load('roles:id,name');

        return view('educator.enrollment.student', compact('user'));
    }

    public function create(): View
    {
        $this->authorize('create', Enrolled::class);

        return view('educator.enrollment.create', [
            'students' => User::where('user_type', 'student')->orderBy('surname')->get(),
            'subjects' => Subject::visibleTo(Auth::user())->with('section:id,section_name')->orderBy('subject_code')->get(),
        ]);
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Enrolled::class);

        $data = $request->validated();
        $created = 0;
        foreach ($data['student_ids'] as $studentId) {
            foreach ($data['subject_ids'] as $subjectId) {
                $row = Enrolled::firstOrCreate(
                    ['educator_id' => Auth::id(), 'student_id' => $studentId, 'subject_id' => $subjectId],
                    ['is_active' => $data['is_active']],
                );
                if ($row->wasRecentlyCreated) {
                    $created++;
                    $this->notifications->emit(Auth::user(), 'enrollment_created', (int) $studentId, [
                        'subject_id' => (int) $subjectId,
                        'title' => 'Enrolled in a new subject',
                        'link_path' => route('student.assessments.index'),
                    ]);
                }
            }
        }

        return redirect()->route('educator.enrollment.index')->with('status', "Created {$created} enrollment(s).");
    }

    public function edit(Enrolled $enrolled): View
    {
        $this->authorize('update', $enrolled);

        return view('educator.enrollment.edit', [
            'enrolled' => $enrolled,
            'students' => User::where('user_type', 'student')->orderBy('surname')->get(),
            'subjects' => Subject::visibleTo(Auth::user())->with('section:id,section_name')->orderBy('subject_code')->get(),
        ]);
    }

    public function update(UpdateEnrollmentRequest $request, Enrolled $enrolled): RedirectResponse
    {
        $this->authorize('update', $enrolled);

        $enrolled->update($request->validated());
        $this->notifications->emit(Auth::user(), 'enrollment_updated', $enrolled->student_id, [
            'subject_id' => $enrolled->subject_id,
            'title' => 'Enrollment updated',
            'link_path' => route('student.assessments.index'),
        ]);

        return redirect()->route('educator.enrollment.index')->with('status', 'Enrollment updated.');
    }

    public function destroy(Enrolled $enrolled): RedirectResponse
    {
        $this->authorize('delete', $enrolled);

        $studentId = $enrolled->student_id;
        $subjectId = $enrolled->subject_id;
        $enrolled->delete();
        // enrollment_deleted: the row is gone, authorizer verifies by subject ownership.
        $this->notifications->emit(Auth::user(), 'enrollment_deleted', $studentId, [
            'subject_id' => $subjectId,
            'title' => 'Unenrolled from a subject',
            'link_path' => route('student.assessments.index'),
        ]);

        return redirect()->route('educator.enrollment.index')->with('status', 'Enrollment removed.');
    }

    // Task 39 bulk import.
    public function importTemplate()
    {
        $this->authorize('create', Enrolled::class);

        return Excel::download(new EnrollmentImportTemplateExport, 'enrollment-upload-template.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Enrolled::class);

        $request->validate([
            'file' => ['required', 'array', 'min:1'],
            'file.*' => ['required', 'file', 'mimes:xlsx'],
        ]);

        foreach ($request->file('file') as $file) {
            $path = $file->storeAs('imports/uploads', uniqid('enrollments-', true).'.'.$file->getClientOriginalExtension(), 'local');

            $import = EnrollmentImport::create([
                'initiated_by_user_id' => $request->user()->id,
                'original_filename' => $file->getClientOriginalName(),
                'upload_path' => $path,
                'status' => 'queued',
            ]);

            ProcessEnrollmentImport::dispatch($import);
        }

        $count = count($request->file('file'));

        return redirect()->route('educator.enrollment.index')
            ->with('status', $count === 1 ? 'Enrollment import queued.' : "{$count} enrollment imports queued.");
    }

    public function importTimeline(Request $request): View
    {
        $this->authorize('create', Enrolled::class);

        $imports = EnrollmentImport::ownedBy($request->user())->latest()->take(6)->get();

        return view('educator.enrollment._import-timeline', compact('imports'));
    }

    public function cancelImport(EnrollmentImport $enrollmentImport): RedirectResponse
    {
        $this->authorize('view', $enrollmentImport);

        if ($enrollmentImport->status !== 'queued') {
            return redirect()->route('educator.enrollment.index')
                ->with('status', 'Only queued enrollment imports can be cancelled.');
        }

        Storage::disk('local')->delete($enrollmentImport->upload_path);
        $enrollmentImport->forceFill([
            'status' => 'cancelled',
            'error_message' => null,
        ])->save();

        return redirect()->route('educator.enrollment.index')
            ->with('status', 'Enrollment import cancelled.');
    }

    public function download()
    {
        $this->authorize('viewAny', Enrolled::class);

        return $this->exports->download(Auth::user());
    }

    public function clearImportHistory(Request $request): RedirectResponse
    {
        $this->authorize('create', Enrolled::class);

        EnrollmentImport::ownedBy($request->user())->get()->each(function (EnrollmentImport $import): void {
            Storage::disk('local')->delete(array_filter([$import->upload_path, $import->failed_report_path]));
            $import->delete();
        });

        return redirect()->route('educator.enrollment.index')->with('status', 'Enrollment import history cleared.');
    }

    public function showImport(EnrollmentImport $enrollmentImport): View
    {
        $this->authorize('view', $enrollmentImport);

        return view('educator.enrollment.import-show', compact('enrollmentImport'));
    }

    public function downloadImportReport(EnrollmentImport $enrollmentImport)
    {
        $this->authorize('view', $enrollmentImport);

        abort_unless($enrollmentImport->failed_report_path, 404);
        abort_unless(Storage::disk('local')->exists($enrollmentImport->failed_report_path), 404);

        return Storage::disk('local')->download($enrollmentImport->failed_report_path, 'enrollment-upload-failed.xlsx');
    }
}
