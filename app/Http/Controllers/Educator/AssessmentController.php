<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Section;
use App\Models\Subject;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// G5: educator assessments. status inactive→active is the publish trigger → notify enrolled students.
class AssessmentController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(): View
    {
        $this->authorize('viewAny', Assessment::class);

        $assessments = Assessment::visibleTo(Auth::user())
            ->with(['subject:id,subject_code,subject_name', 'section:id,section_name', 'academicTerm:id,term_name'])
            ->orderByDesc('id')->get();

        return view('educator.assessments.index', compact('assessments'));
    }

    public function create(): View
    {
        $this->authorize('create', Assessment::class);

        return view('educator.assessments.create', $this->formData());
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Assessment::class);

        $data = $request->validated();
        $subjectIds = $data['subject_ids'];
        unset($data['subject_ids']);

        // One assessment per selected subject; section_id derived from each subject's own section.
        $subjects = Subject::whereKey($subjectIds)->get(['id', 'sections_id']);
        foreach ($subjects as $subject) {
            $assessment = Assessment::create($data + [
                'educator_id' => Auth::id(),
                'subject_id'  => $subject->id,
                'section_id'  => $subject->sections_id,
            ]);

            // Publish-on-create: if created already active, notify enrolled students.
            if ($assessment->is_active) {
                $this->notifyEnrolled($assessment, 'assessment_created', 'New assessment published');
            }
        }

        $n = $subjects->count();
        return redirect()->route('educator.assessments.index')
            ->with('status', $n === 1 ? 'Assessment created.' : "{$n} assessments created.");
    }

    public function edit(Assessment $assessment): View
    {
        $this->authorize('update', $assessment);

        return view('educator.assessments.edit', ['assessment' => $assessment] + $this->formData());
    }

    public function update(UpdateAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $wasActive = $assessment->is_active;
        $data = $request->validated();
        $subjectIds = $data['subject_ids'];
        unset($data['subject_ids']);

        // Update the current row against its own subject (section follows the subject).
        $assessment->update($data + ['section_id' => $assessment->subject->sections_id]);

        // Publish trigger: inactive → active fires assessment_created; otherwise assessment_updated.
        if (! $wasActive && $assessment->is_active) {
            $this->notifyEnrolled($assessment, 'assessment_created', 'New assessment published');
        } else {
            $this->notifyEnrolled($assessment, 'assessment_updated', 'Assessment updated');
        }

        // Any additional selected subjects become NEW assessments (one per subject).
        $extraIds = array_diff($subjectIds, [$assessment->subject_id]);
        $created = Subject::whereKey($extraIds)->get(['id', 'sections_id']);
        foreach ($created as $subject) {
            $new = Assessment::create($data + [
                'educator_id' => Auth::id(),
                'subject_id'  => $subject->id,
                'section_id'  => $subject->sections_id,
            ]);
            if ($new->is_active) {
                $this->notifyEnrolled($new, 'assessment_created', 'New assessment published');
            }
        }

        $extra = $created->count();
        return redirect()->route('educator.assessments.index')
            ->with('status', $extra ? "Assessment updated; {$extra} added." : 'Assessment updated.');
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->authorize('delete', $assessment);

        $this->notifyEnrolled($assessment, 'assessment_deleted', 'Assessment removed');
        $assessment->delete(); // FK cascade removes quizzes

        return redirect()->route('educator.assessments.index')->with('status', 'Assessment deleted.');
    }

    private function notifyEnrolled(Assessment $assessment, string $event, string $title): void
    {
        $studentIds = Enrolled::where('educator_id', Auth::id())
            ->where('subject_id', $assessment->subject_id)
            ->where('is_active', true)
            ->pluck('student_id')->all();

        $this->notifications->emitToMany(Auth::user(), $event, $studentIds, [
            'subject_id' => $assessment->subject_id, 'assessment_id' => $assessment->id,
            'section_id' => $assessment->section_id, 'title' => $title,
        ]);
    }

    private function formData(): array
    {
        return [
            'subjects' => Subject::visibleTo(Auth::user())->with('section:id,section_name')->orderBy('subject_code')->get(),
            'sections' => Section::visibleTo(Auth::user())->orderBy('section_name')->get(),
            'terms' => AcademicTerm::with('year')->get(),
        ];
    }
}
