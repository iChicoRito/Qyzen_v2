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
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

// G4: enrollment. Single + bulk xlsx, deduped, with enrollment_created/updated/deleted
// notifications to students only (best-effort via NotificationService).
class EnrollmentController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(): View
    {
        $this->authorize('viewAny', Enrolled::class);

        // One row per subject/section (grouped), not per enrollment. Students live on the detail page.
        $subjects = Subject::visibleTo(Auth::user())
            ->has('enrollments')
            ->with('section:id,section_name')
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('subject_code')->get();

        $imports = EnrollmentImport::ownedBy(Auth::user())->latest()->take(6)->get();

        return view('educator.enrollment.index', compact('subjects', 'imports'));
    }

    // Detail page: all students enrolled in one subject/section (mirrors scores.show).
    public function showSubject(Subject $subject): View
    {
        $this->authorize('viewAny', Enrolled::class);
        abort_unless(Subject::visibleTo(Auth::user())->whereKey($subject->getKey())->exists(), 403);

        $subject->load('section:id,section_name');
        $enrollments = Enrolled::where('subject_id', $subject->id)->visibleTo(Auth::user())
            ->with('student:id,given_name,surname,user_id')
            ->orderByDesc('id')->get();

        return view('educator.enrollment.show', compact('subject', 'enrollments'));
    }

    public function create(): View
    {
        $this->authorize('create', Enrolled::class);

        return view('educator.enrollment.create', [
            'students' => User::where('user_type', 'student')->orderBy('surname')->get(),
            'subjects' => Subject::visibleTo(Auth::user())->orderBy('subject_code')->get(),
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
            'subjects' => Subject::visibleTo(Auth::user())->orderBy('subject_code')->get(),
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

    public function showImport(EnrollmentImport $enrollmentImport): View
    {
        $this->authorize('view', $enrollmentImport);

        return view('educator.enrollment.import-show', compact('enrollmentImport'));
    }
}
