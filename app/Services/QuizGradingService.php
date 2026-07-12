<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\Score;
use App\Models\StudentAssessmentAccess;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// H6 ⚠ THE CORE INVARIANT. Grading happens here, server-side only:
//  - correct_answer is loaded from the DB inside this service and NEVER returned to the caller
//    or serialized to the client. The Quiz model also marks it $hidden as a second guard.
//  - pass mark is >= 75%, computed on the server from the freshly loaded correct answers.
// The student's submitted answers come in; only an authoritative Score row goes back.
class QuizGradingService
{
    public const PASS_THRESHOLD = 0.75;

    public function __construct(
        private NotificationService $notifications,
        private QuestionPoolDrawService $pool,
    ) {}

    /**
     * Save an in-progress draft (mode=draft). No grading, no correct_answer touched.
     *
     * @param  array<int|string,string>  $answers  questionId => answer
     */
    public function saveDraft(User $student, Assessment $assessment, array $answers, int $warnings = 0): Score
    {
        // firstOrNew (not updateOrCreate) so taken_at is set ONCE, on the first save — it anchors
        // the true attempt start for the server-authoritative timer. Overwriting it each autosave
        // would let the countdown reset on every refresh.
        $score = Score::firstOrNew(
            ['student_id' => $student->id, 'assessment_id' => $assessment->id, 'status' => 'in_progress'],
        );

        // Task 51: the pool draw is pinned once, on the first save for this attempt — never
        // re-rolled on later autosaves, or a refresh could show a different subset than grading
        // and the stored answers are keyed against.
        $drawnQuizIds = $score->exists ? $score->drawn_quiz_ids : $this->pool->drawFor($assessment);

        $score->fill([
            'educator_id' => $assessment->educator_id,
            'subject_id' => $assessment->subject_id,
            'section_id' => $assessment->section_id,
            'student_answer' => $answers,
            'warning_attempts' => $warnings,
            'drawn_quiz_ids' => $drawnQuizIds,
            'total_questions' => count($drawnQuizIds),
        ]);
        $score->taken_at ??= now();
        $score->save();

        return $score;
    }

    /**
     * Grade and finalize a submission (mode=submit). Loads correct answers server-side,
     * compares, writes the score, notifies the educator. Returns the graded Score.
     *
     * @param  array<int|string,string>  $answers  questionId => answer
     */
    public function grade(User $student, Assessment $assessment, array $answers, int $warnings = 0): Score
    {
        return DB::transaction(function () use ($student, $assessment, $answers, $warnings) {
            // Reuse the in-progress row if present, else create a fresh attempt. take()/saveDraft()
            // always create this first, so drawn_quiz_ids should already be pinned here.
            $score = Score::firstOrNew([
                'student_id' => $student->id, 'assessment_id' => $assessment->id, 'status' => 'in_progress',
            ]);
            $drawnQuizIds = $score->drawn_quiz_ids ?: $this->pool->drawFor($assessment);

            // correct_answer is explicitly selected here — server side, never leaves this method.
            $questions = Quiz::whereIn('id', $drawnQuizIds)
                ->get(['id', 'quiz_type', 'correct_answer']);

            $total = $questions->count();
            $correct = 0;
            foreach ($questions as $q) {
                $given = $answers[$q->id] ?? ($answers[(string) $q->id] ?? null);
                if ($given !== null && $this->isCorrect($q, (string) $given)) {
                    $correct++;
                }
            }

            $isPassed = $total > 0 && ($correct / $total) >= self::PASS_THRESHOLD;

            $score->fill([
                'educator_id' => $assessment->educator_id,
                'subject_id' => $assessment->subject_id,
                'section_id' => $assessment->section_id,
                'student_answer' => $answers,
                'warning_attempts' => $warnings,
                'drawn_quiz_ids' => $drawnQuizIds,
                'score' => $correct,
                'total_questions' => $total,
                'is_passed' => $isPassed,
                'status' => $isPassed ? 'passed' : 'failed',
                'submitted_at' => now(),
            ]);
            $score->taken_at ??= now();
            $score->save();

            StudentAssessmentAccess::where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->where('is_active', true)
                ->where('updated_at', '<=', $score->submitted_at)
                ->update(['is_active' => false, 'updated_at' => now()]);

            // quiz_submitted → the assessment's educator (student emit rule, D5).
            $this->notifications->emit($student, 'quiz_submitted', $assessment->educator_id, [
                'assessment_id' => $assessment->id,
                'subject_id' => $assessment->subject_id,
                'section_id' => $assessment->section_id,
                'title' => 'A student submitted an assessment',
                'link_path' => route('educator.scores.index'),
            ]);

            return $score;
        });
    }

    /**
     * Task 02: reveal one question's correct answer as a hint, bounded by hint_count. Deliberate,
     * educator-opted-in exception to the "correct_answer never reaches the client" guarantee above
     * — that guarantee is about unsolicited exposure; this is a counted reveal the educator turned
     * on via allow_hint. Returns null when hints are off, exhausted, or there's no active attempt
     * (nothing deducted). Otherwise ALWAYS deducts one hint credit — win or lose, exactly one
     * credit per mini-game attempt — and only includes the answer text when $won is true.
     *
     * @return array{answer: ?string}|null
     */
    public function revealHint(User $student, Assessment $assessment, Quiz $quiz, bool $won): ?array
    {
        if (! $assessment->allow_hint) {
            return null;
        }

        $score = Score::where('student_id', $student->id)
            ->where('assessment_id', $assessment->id)
            ->where('status', 'in_progress')
            ->first();

        if (! $score || $score->hints_used >= $assessment->hint_count) {
            return null;
        }

        $score->increment('hints_used');

        return ['answer' => $won ? $this->answerText($quiz) : null];
    }

    // Human-readable correct-answer text: the choice label for multiple choice, or the first
    // acceptable value for identification (which may store a single answer or a JSON array).
    private function answerText(Quiz $quiz): string
    {
        $correct = $quiz->correct_answer;
        if ($quiz->quiz_type === 'multiple_choice' && is_array($quiz->choices)) {
            return (string) ($quiz->choices[$correct] ?? $correct);
        }

        $decoded = json_decode($correct, true);

        return is_array($decoded) ? (string) reset($decoded) : $correct;
    }

    // public so the results/review screen reuses the exact grading rule (incl. JSON-array
    // identification answers) — review and grading must never disagree.
    public function isCorrect(Quiz $quiz, string $given): bool
    {
        $correct = $quiz->correct_answer;

        // identification may store a single answer or a JSON array of acceptable answers.
        $decoded = json_decode($correct, true);
        if (is_array($decoded)) {
            return collect($decoded)->contains(fn ($a) => $this->norm((string) $a) === $this->norm($given));
        }

        return $this->norm($correct) === $this->norm($given);
    }

    private function norm(string $v): string
    {
        return strtolower(trim($v));
    }
}
