<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\Score;
use App\Services\AssessmentAvailabilityService;
use App\Services\QuizGradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// H2–H6: student quiz engine. Enrollment-gated (visibleTo) + schedule + attempt gates.
// correct_answer NEVER reaches the client — take views select only id/question/type/choices,
// and grading is server-side (QuizGradingService).
class QuizController extends Controller
{
    public function __construct(
        private AssessmentAvailabilityService $availability,
        private QuizGradingService $grading,
    ) {}

    // H2: enrolled assessments + availability badge + can-take.
    public function index(Request $request): View
    {
        $user = Auth::user();

        $assessments = Assessment::visibleTo($user)
            ->with(['subject:id,subject_code,subject_name', 'section:id,section_name'])
            ->orderByDesc('id')->get()
            ->map(function (Assessment $a) use ($user) {
                $a->setAttribute('availability', $this->availability->summarize($a, $user->id));

                return $a;
            });

        $tab = $request->query('tab', 'pending'); // pending | finished

        return view('student.assessments.index', compact('assessments', 'tab'));
    }

    // H2: details panel.
    public function details(Assessment $assessment): View
    {
        $this->authorize('view', $assessment); // enrollment-gated via AssessmentPolicy

        $availability = $this->availability->summarize($assessment, Auth::id());
        $questionCount = Quiz::where('assessment_id', $assessment->id)->count();
        $attempts = Score::where('assessment_id', $assessment->id)
            ->where('student_id', Auth::id())
            ->orderByDesc('submitted_at')->get(['id', 'score', 'total_questions', 'is_passed', 'status', 'submitted_at']);

        return view('student.assessments.details', compact('assessment', 'availability', 'questionCount', 'attempts'));
    }

    // H3: take-quiz session load — eligibility + draft restore + shuffle.
    public function take(Assessment $assessment): View|RedirectResponse
    {
        $this->authorize('view', $assessment);

        $summary = $this->availability->summarize($assessment, Auth::id());
        if (! $summary['can_take']) {
            return redirect()->route('student.assessments.details', $assessment)
                ->with('status', 'This assessment is not available to take right now.');
        }

        // Questions WITHOUT correct_answer (model hides it; we also select explicit columns).
        $questions = Quiz::where('assessment_id', $assessment->id)
            ->get(['id', 'question', 'quiz_type', 'choices']);

        if ($questions->isEmpty()) {
            return redirect()->route('student.assessments.details', $assessment)
                ->with('status', 'This assessment has no questions yet.');
        }

        if ($assessment->is_shuffle) {
            $questions = $questions->shuffle()->values();
        }

        // Restore an in-progress draft if present.
        $draft = Score::where('assessment_id', $assessment->id)
            ->where('student_id', Auth::id())
            ->where('status', 'in_progress')->first();

        return view('student.take-quiz', [
            'assessment' => $assessment,
            'questions' => $questions,
            'draftAnswers' => $draft?->student_answer ?? [],
            'warnings' => $draft?->warning_attempts ?? 0,
        ]);
    }

    // H4: autosave draft (debounced fetch). Returns minimal JSON, never correct_answer.
    public function saveDraft(Request $request, Assessment $assessment)
    {
        $this->authorize('view', $assessment);

        $data = $request->validate([
            'answers' => ['array'],
            'warnings' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->grading->saveDraft(Auth::user(), $assessment, $data['answers'] ?? [], (int) ($data['warnings'] ?? 0));

        return response()->json(['saved' => true, 'at' => now()->toTimeString()]);
    }

    // H6 ⚠ submit → server-side grading. correct_answer is loaded server-side in the service only.
    public function submit(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('view', $assessment);

        // Re-validate eligibility server-side (don't trust the client's gate).
        $summary = $this->availability->summarize($assessment, Auth::id());
        if (! $summary['can_take']) {
            return redirect()->route('student.assessments.details', $assessment)
                ->with('status', 'This attempt is no longer eligible.');
        }

        $data = $request->validate([
            'answers' => ['array'],
            'warnings' => ['nullable', 'integer', 'min:0'],
        ]);

        $score = $this->grading->grade(Auth::user(), $assessment, $data['answers'] ?? [], (int) ($data['warnings'] ?? 0));

        return redirect()->route('student.scores.show', $score)->with('status', 'Submitted and graded.');
    }
}
