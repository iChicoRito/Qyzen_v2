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
use Illuminate\Http\Response;
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
            ->with(['subject:id,subject_code,subject_name', 'section:id,section_name', 'academicTerm:id,term_name'])
            ->withCount('quizzes')
            ->orderByDesc('id')->get()
            ->map(function (Assessment $a) use ($user) {
                $av = $this->availability->summarize($a, $user->id);
                $a->setAttribute('availability', $av);
                [$label, $color] = $this->displayStatus($av['badge'], (int) $a->quizzes_count);
                // Task 21: a finished, no-retake-left attempt reads "Already Taken" (was
                // mislabelled "Available"). Takes precedence — it can never be retaken.
                if ($av['submitted_attempts'] > 0 && $av['remaining'] === 0) {
                    [$label, $color] = ['Already Taken', 'secondary'];
                }
                $a->setAttribute('status_label', $label);
                $a->setAttribute('status_color', $color);
                // Can only start when takeable AND questions exist (take() redirects on empty).
                $a->setAttribute('startable', $av['can_take'] && $a->quizzes_count > 0);

                return $a;
            });

        $tab = $request->query('tab', 'pending'); // pending | finished

        return view('student.assessments.index', compact('assessments', 'tab'));
    }

    /**
     * Student-friendly card status. Time gates take precedence, then question readiness.
     *
     * @return array{0:string,1:string} [label, kt-badge color]
     */
    private function displayStatus(string $badge, int $questionCount): array
    {
        return match (true) {
            $badge === 'Upcoming'       => ['Starts Soon', 'warning'],
            $badge === 'Expired'        => ['No Longer Available', 'secondary'],
            $badge === 'Schedule issue' => ['Not Ready Yet', 'secondary'],
            $questionCount === 0        => ['Not Ready Yet', 'secondary'],
            $badge === 'Reopened'       => ['Reopened', 'info'],
            default                     => ['Available', 'success'],
        };
    }

    // H2: details panel.
    public function details(Assessment $assessment): View
    {
        $this->authorize('view', $assessment); // enrollment-gated via AssessmentPolicy

        $availability = $this->availability->summarize($assessment, Auth::id());
        $questionCount = Quiz::where('assessment_id', $assessment->id)->count();
        $attempts = Score::where('assessment_id', $assessment->id)
            ->where('student_id', Auth::id())
            ->orderByDesc('submitted_at')->get(['id', 'uuid', 'score', 'total_questions', 'is_passed', 'status', 'submitted_at']);

        return view('student.assessments.details', compact('assessment', 'availability', 'questionCount', 'attempts'));
    }

    // H3: take-quiz session load — eligibility + draft restore + stable shuffle + server timer.
    public function take(Assessment $assessment): Response|RedirectResponse
    {
        $this->authorize('view', $assessment);

        // Post-submit lock: a finished attempt can't be reloaded. If no attempt remains, send the
        // student to their latest result (or the list) rather than back into the quiz.
        $summary = $this->availability->summarize($assessment, Auth::id());
        if (! $summary['can_take']) {
            $latest = Score::where('assessment_id', $assessment->id)
                ->where('student_id', Auth::id())
                ->whereIn('status', ['passed', 'failed', 'submitted'])
                ->orderByDesc('submitted_at')->first();

            return $latest
                ? redirect()->route('student.scores.show', $latest)
                    ->with('status', 'You have already completed this assessment.')
                : redirect()->route('student.assessments.index')
                    ->with('status', 'This assessment is not available to take right now.');
        }

        // Questions WITHOUT correct_answer (model hides it; we also select explicit columns).
        $questions = Quiz::where('assessment_id', $assessment->id)
            ->get(['id', 'question', 'quiz_type', 'choices']);

        if ($questions->isEmpty()) {
            return redirect()->route('student.assessments.details', $assessment)
                ->with('status', 'This assessment has no questions yet.');
        }

        // Anchor the attempt: ensure the in-progress row exists so taken_at (the true start
        // for the timer) is fixed on first screen load, before any autosave.
        $draft = Score::where('assessment_id', $assessment->id)
            ->where('student_id', Auth::id())
            ->where('status', 'in_progress')->first()
            ?? $this->grading->saveDraft(Auth::user(), $assessment, [], 0);

        // Task 21: shuffle a fresh order on every load when is_shuffle is on. Answers are keyed by
        // question id (name="answers[{id}]") and MC values are the choice key, so reordering the
        // display never breaks draft-answer restore or grading.
        if ($assessment->is_shuffle) {
            $questions = $questions->shuffle();
            $questions->each(function (Quiz $q) {
                if ($q->quiz_type === 'multiple_choice' && is_array($q->choices)) {
                    $keys = array_keys($q->choices);
                    shuffle($keys);
                    $q->setAttribute('choices', array_replace(array_flip($keys), $q->choices));
                }
            });
        }

        // Server-authoritative remaining time: resume from real elapsed since taken_at, not a
        // fresh countdown each load. Time keeps running while the student is away.
        $timeLimitSec = ((int) $assessment->time_limit) * 60;
        $remainingSeconds = $timeLimitSec > 0
            ? (int) max(0, $timeLimitSec - $draft->taken_at->diffInSeconds(now()))
            : 0;

        $assessment->load([
            'subject:id,subject_code,subject_name',
            'section:id,section_name',
            'educator:id,given_name,surname',
            'academicTerm:id,term_name',
        ]);

        // no-store so the Back button can't re-show a finished quiz from cache/bfcache — it
        // refetches and hits the can_take gate above.
        return response()->view('student.take-quiz', [
            'assessment' => $assessment,
            'questions' => $questions,
            'draftAnswers' => $draft->student_answer ?? [],
            'warnings' => $draft->warning_attempts ?? 0,
            'remainingSeconds' => $remainingSeconds,
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
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
