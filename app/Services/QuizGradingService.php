<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\Score;
use App\Models\StudentAssessmentAccess;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// H6: THE CORE INVARIANT. Grading happens here, server-side only:
//  - correct_answer is loaded from the DB inside this service and NEVER returned to the caller
//    or serialized to the client. The Quiz model also marks it $hidden as a second guard.
//  - pass mark is >= 70%, computed on the server from the freshly loaded correct answers.
// The student's submitted answers come in; only an authoritative Score row goes back.
class QuizGradingService
{
    public const PASS_THRESHOLD = 0.70;

    private const TERMINAL_STATUSES = ['submitted', 'passed', 'failed'];

    public function __construct(
        private NotificationService $notifications,
        private QuestionPoolDrawService $pool,
    ) {}

    /**
     * Start or return the active attempt. This is the only draft-creation path.
     */
    public function startAttempt(User $student, Assessment $assessment): Score
    {
        return DB::transaction(function () use ($student, $assessment) {
            $score = Score::where('student_id', $student->id)
                ->where('assessment_id', $assessment->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if ($score) {
                return $score;
            }

            $drawnQuizIds = $this->pool->drawFor($assessment);

            $score = new Score([
                'student_id' => $student->id,
                'assessment_id' => $assessment->id,
                'status' => 'in_progress',
            ]);
            $score->fill([
                'educator_id' => $assessment->educator_id,
                'subject_id' => $assessment->subject_id,
                'section_id' => $assessment->section_id,
                'student_answer' => [],
                'warning_attempts' => 0,
                'drawn_quiz_ids' => $drawnQuizIds,
                'total_questions' => count($drawnQuizIds),
            ]);
            $score->taken_at ??= now();
            $score->save();

            return $score;
        });
    }

    /**
     * Save an existing in-progress draft. No grading, no correct_answer touched.
     *
     * @param  array<int|string,string>  $answers  questionId => answer
     */
    public function saveDraft(User $student, Assessment $assessment, array $answers, int $warnings = 0): ?Score
    {
        return DB::transaction(function () use ($student, $assessment, $answers) {
            $score = Score::where('student_id', $student->id)
                ->where('assessment_id', $assessment->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if (! $score) {
                return null;
            }

            $drawnQuizIds = $score->drawn_quiz_ids ?: $this->pool->drawFor($assessment);

            $score->fill([
                'educator_id' => $assessment->educator_id,
                'subject_id' => $assessment->subject_id,
                'section_id' => $assessment->section_id,
                'student_answer' => $answers,
                'drawn_quiz_ids' => $drawnQuizIds,
                'total_questions' => count($drawnQuizIds),
            ]);
            $score->taken_at ??= now();
            $score->save();

            return $score;
        });
    }

    /**
     * Persist one integrity warning and force-submit when the assessment limit is reached.
     *
     * @param  array<int|string,string>  $answers  questionId => answer
     */
    public function recordWarning(User $student, Assessment $assessment, array $answers): Score
    {
        return DB::transaction(function () use ($student, $assessment, $answers) {
            $score = Score::where('student_id', $student->id)
                ->where('assessment_id', $assessment->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if (! $score) {
                $terminal = $this->latestTerminalAttempt($student, $assessment);
                if ($terminal) {
                    return $terminal;
                }

                $score = new Score([
                    'student_id' => $student->id,
                    'assessment_id' => $assessment->id,
                    'status' => 'in_progress',
                    'warning_attempts' => 0,
                ]);
            }

            $drawnQuizIds = $score->drawn_quiz_ids ?: $this->pool->drawFor($assessment);
            $score->fill([
                'educator_id' => $assessment->educator_id,
                'subject_id' => $assessment->subject_id,
                'section_id' => $assessment->section_id,
                'student_answer' => $answers,
                'warning_attempts' => ((int) $score->warning_attempts) + 1,
                'drawn_quiz_ids' => $drawnQuizIds,
                'total_questions' => count($drawnQuizIds),
            ]);
            $score->taken_at ??= now();
            $score->save();

            $limit = (int) $assessment->cheating_attempts;
            if ($limit > 0 && $score->warning_attempts >= $limit) {
                return $this->finalizeScore($score, $student, $assessment, $answers, $drawnQuizIds);
            }

            return $score;
        });
    }

    /**
     * Grade and finalize a submission (mode=submit). Loads correct answers server-side,
     * compares, writes the score, notifies the educator. Returns the graded Score.
     *
     * @param  array<int|string,string>  $answers  questionId => answer
     */
    public function grade(User $student, Assessment $assessment, array $answers, int $warnings = 0): Score
    {
        return DB::transaction(function () use ($student, $assessment, $answers) {
            $score = Score::where('student_id', $student->id)
                ->where('assessment_id', $assessment->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if (! $score) {
                $existing = $this->latestTerminalAttempt($student, $assessment);
                if ($existing) {
                    return $existing;
                }

                $score = new Score([
                    'student_id' => $student->id,
                    'assessment_id' => $assessment->id,
                    'status' => 'in_progress',
                    'warning_attempts' => 0,
                ]);
            }

            $drawnQuizIds = $score->drawn_quiz_ids ?: $this->pool->drawFor($assessment);

            return $this->finalizeScore($score, $student, $assessment, $answers, $drawnQuizIds);
        });
    }

    /**
     * @param  array<int|string,string>  $answers
     * @param  array<int>  $drawnQuizIds
     */
    private function finalizeScore(Score $score, User $student, Assessment $assessment, array $answers, array $drawnQuizIds): Score
    {
        if (in_array($score->status, self::TERMINAL_STATUSES, true)) {
            return $score;
        }

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

        $this->notifications->emit($student, 'quiz_submitted', $assessment->educator_id, [
            'assessment_id' => $assessment->id,
            'subject_id' => $assessment->subject_id,
            'section_id' => $assessment->section_id,
            'title' => 'A student submitted an assessment',
            'link_path' => route('educator.scores.index'),
        ]);

        return $score;
    }

    private function latestTerminalAttempt(User $student, Assessment $assessment): ?Score
    {
        return Score::where('student_id', $student->id)
            ->where('assessment_id', $assessment->id)
            ->whereIn('status', self::TERMINAL_STATUSES)
            ->latest('submitted_at')
            ->first();
    }

    /**
     * Task 02: reveal one question's correct answer as a hint, bounded by hint_count. Deliberate,
     * educator-opted-in exception to the "correct_answer never reaches the client" guarantee above.
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

    private function answerText(Quiz $quiz): string
    {
        $correct = $quiz->correct_answer;
        if ($quiz->quiz_type === 'multiple_choice' && is_array($quiz->choices)) {
            return (string) ($quiz->choices[$correct] ?? $correct);
        }

        $decoded = json_decode($correct, true);

        return is_array($decoded) ? (string) reset($decoded) : $correct;
    }

    public function isCorrect(Quiz $quiz, string $given): bool
    {
        $correct = $quiz->correct_answer;

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
