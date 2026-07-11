<?php

namespace App\Http\Controllers\Educator;

use App\Events\ConversationActivity;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Section;
use App\Models\StudentAssessmentAccess;
use App\Models\StudentAssessmentExemption;
use App\Models\Subject;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\NotificationService;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// G5: educator assessments. status inactive→active is the publish trigger → notify enrolled students.
class AssessmentController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private ConversationService $conversations,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Assessment::class);

        $selectedSubject = $request->query('subject');
        $selectedSection = $request->query('section');
        $query = Assessment::query()
            ->where('tbl_assessments.educator_id', Auth::id())
            ->with(['subject:id,subject_code,subject_name', 'section:id,section_name', 'academicTerm:id,term_name']);
        TableQuery::search($query, $request->query('search'), [
            'assessment_code',
            fn (Builder $q, string $term) => $q->orWhereHas('subject', fn ($s) => $s->where('subject_code', 'like', "%{$term}%")->orWhere('subject_name', 'like', "%{$term}%")),
            fn (Builder $q, string $term) => $q->orWhereHas('section', fn ($s) => $s->where('section_name', 'like', "%{$term}%")),
        ]);
        TableQuery::filters($query, $request, ['subject' => 'subject_id', 'section' => 'section_id', 'assessment' => 'assessment_code', 'status' => 'is_active']);
        TableQuery::sort($query, $request, [
            'code' => 'assessment_code',
            'subject' => function (Builder $q, string $direction): void {
                $q->leftJoin('tbl_subjects as sort_subjects', 'sort_subjects.id', '=', 'tbl_assessments.subject_id')
                    ->select('tbl_assessments.*')
                    ->orderBy('sort_subjects.subject_name', $direction)
                    ->orderBy('tbl_assessments.id', 'desc');
            },
            'section' => function (Builder $q, string $direction): void {
                $q->leftJoin('tbl_sections as sort_sections', 'sort_sections.id', '=', 'tbl_assessments.section_id')
                    ->select('tbl_assessments.*')
                    ->orderBy('sort_sections.section_name', $direction)
                    ->orderBy('tbl_assessments.id', 'desc');
            },
            'term' => function (Builder $q, string $direction): void {
                $q->leftJoin('tbl_academic_term as sort_terms', 'sort_terms.id', '=', 'tbl_assessments.term')
                    ->select('tbl_assessments.*')
                    ->orderBy('sort_terms.term_name', $direction)
                    ->orderBy('tbl_assessments.id', 'desc');
            },
            'window' => function (Builder $q, string $direction): void {
                $q->orderBy('start_date', $direction)
                    ->orderBy('end_date', $direction)
                    ->orderBy('id', 'desc');
            },
            'status' => 'is_active',
            'id' => 'id',
        ], 'id', 'desc');

        $assessments = $query->paginate(TableQuery::perPage($request))->withQueryString();

        $filterSubjects = Subject::visibleTo(Auth::user())
            ->when($selectedSection, fn ($q) => $q->where('sections_id', $selectedSection))
            ->orderBy('subject_code')->get(['id', 'subject_code', 'subject_name', 'sections_id']);
        $filterSections = Section::visibleTo(Auth::user())->orderBy('section_name')->get(['id', 'section_name']);
        $filterAssessments = Assessment::visibleTo(Auth::user())
            ->when($selectedSection, fn ($q) => $q->whereHas('subject', fn ($s) => $s->where('sections_id', $selectedSection)))
            ->when($selectedSubject, fn ($q) => $q->where('subject_id', $selectedSubject))
            ->select('assessment_code')
            ->distinct()
            ->orderBy('assessment_code')
            ->pluck('assessment_code');

        return view('educator.assessments.index', compact('assessments', 'filterSubjects', 'filterSections', 'filterAssessments'));
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
                'subject_id' => $subject->id,
                'section_id' => $subject->sections_id,
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
                'subject_id' => $subject->id,
                'section_id' => $subject->sections_id,
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
        // Task 51: FK cascade only removes this assessment's pool-eligibility pivot rows — bank
        // questions are shared/reusable and survive.
        $assessment->delete();

        return redirect()->route('educator.assessments.index')->with('status', 'Assessment deleted.');
    }

    // Task 01: per-student "cannot take this quiz" exemption (e.g. an absent student).
    public function exemptions(Assessment $assessment): View
    {
        $this->authorize('update', $assessment);

        $students = Enrolled::where('educator_id', Auth::id())
            ->where('subject_id', $assessment->subject_id)
            ->where('is_active', true)
            ->with('student:id,given_name,surname,user_id')
            ->get();

        $exemptStudentIds = StudentAssessmentExemption::where('assessment_id', $assessment->id)
            ->where('is_active', true)
            ->pluck('student_id')->all();

        return view('educator.assessments.exemptions', compact('assessment', 'students', 'exemptStudentIds'));
    }

    public function toggleExemption(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
            'action' => ['required', 'in:exempt,unexempt'],
            'reason' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('action') === 'exempt')],
        ]);

        // Only students actually enrolled in this assessment's subject can be exempted — a
        // stray/forged id in the bulk payload is silently dropped, not a hard error.
        $studentIds = Enrolled::where('educator_id', Auth::id())
            ->where('subject_id', $assessment->subject_id)
            ->whereIn('student_id', $data['student_ids'])
            ->pluck('student_id');

        foreach ($studentIds as $studentId) {
            $values = ['is_active' => $data['action'] === 'exempt'];
            if ($data['action'] === 'exempt') {
                $values['reason'] = $data['reason'];
            }

            StudentAssessmentExemption::updateOrCreate([
                'educator_id' => Auth::id(), 'student_id' => $studentId, 'assessment_id' => $assessment->id,
            ], $values);

        }

        if ($data['action'] === 'exempt') {
            $exemptionMessage = 'You have been exempted from assessment '.$assessment->assessment_code.'. Reason: '.$data['reason'];

            $this->notifications->emitToMany(Auth::user(), 'assessment_exempted', $studentIds->all(), [
                'subject_id' => $assessment->subject_id,
                'assessment_id' => $assessment->id,
                'section_id' => $assessment->section_id,
                'title' => 'Assessment exemption',
                'message' => $exemptionMessage,
                'link_path' => route('student.assessments.index'),
                'metadata' => ['reason' => $data['reason']],
            ]);

            foreach ($studentIds as $studentId) {
                $conversation = $this->conversations->findOrCreateConversation(
                    Auth::user(),
                    User::findOrFail($studentId),
                );
                $this->conversations->sendMessage($conversation, Auth::user(), $exemptionMessage);
                broadcast(new ConversationActivity((int) $studentId, $conversation->id));
            }
        }

        $verb = $data['action'] === 'exempt' ? 'exempted' : 'un-exempted';

        // The exemptions view is a modal-only fragment (no page layout) — redirect to the real
        // index page like every other modal-form submission in this app, not back into the
        // fragment route, which would render bare/unstyled on a full browser navigation.
        return redirect()->route('educator.assessments.index')
            ->with('status', $studentIds->count().' student(s) '.$verb.'.');
    }

    public function access(Assessment $assessment): View
    {
        $this->authorize('update', $assessment);

        $students = Enrolled::where('educator_id', Auth::id())
            ->where('subject_id', $assessment->subject_id)
            ->where('is_active', true)
            ->with('student:id,given_name,surname,user_id')
            ->get();

        $accessStudentIds = StudentAssessmentAccess::where('assessment_id', $assessment->id)
            ->where('is_active', true)
            ->pluck('student_id')->all();

        return view('educator.assessments.access', compact('assessment', 'students', 'accessStudentIds'));
    }

    public function toggleAccess(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
            'action' => ['required', 'in:grant,revoke'],
        ]);

        $studentIds = Enrolled::where('educator_id', Auth::id())
            ->where('subject_id', $assessment->subject_id)
            ->whereIn('student_id', $data['student_ids'])
            ->pluck('student_id');

        foreach ($studentIds as $studentId) {
            StudentAssessmentAccess::updateOrCreate([
                'educator_id' => Auth::id(),
                'student_id' => $studentId,
                'assessment_id' => $assessment->id,
            ], [
                'is_active' => $data['action'] === 'grant',
            ]);
        }

        $verb = $data['action'] === 'grant' ? 'granted special access for' : 'revoked special access for';

        return redirect()->route('educator.assessments.index')
            ->with('status', $studentIds->count().' student(s) '.$verb.'.');
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
            'link_path' => route('student.assessments.index'),
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
