<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\Score;
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

    public function __construct(private NotificationService $notifications) {}

    /**
     * Save an in-progress draft (mode=draft). No grading, no correct_answer touched.
     *
     * @param  array<int|string,string>  $answers  questionId => answer
     */
    public function saveDraft(User $student, Assessment $assessment, array $answers, int $warnings = 0): Score
    {
        return Score::updateOrCreate(
            ['student_id' => $student->id, 'assessment_id' => $assessment->id, 'status' => 'in_progress'],
            [
                'educator_id' => $assessment->educator_id,
                'subject_id' => $assessment->subject_id,
                'section_id' => $assessment->section_id,
                'student_answer' => $answers,
                'warning_attempts' => $warnings,
                'total_questions' => Quiz::where('assessment_id', $assessment->id)->count(),
                'taken_at' => now(),
            ],
        );
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
            // correct_answer is explicitly selected here — server side, never leaves this method.
            $questions = Quiz::where('assessment_id', $assessment->id)
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

            // Reuse the in-progress row if present, else create a fresh attempt.
            $score = Score::firstOrNew([
                'student_id' => $student->id, 'assessment_id' => $assessment->id, 'status' => 'in_progress',
            ]);
            $score->fill([
                'educator_id' => $assessment->educator_id,
                'subject_id' => $assessment->subject_id,
                'section_id' => $assessment->section_id,
                'student_answer' => $answers,
                'warning_attempts' => $warnings,
                'score' => $correct,
                'total_questions' => $total,
                'is_passed' => $isPassed,
                'status' => $isPassed ? 'passed' : 'failed',
                'submitted_at' => now(),
            ]);
            $score->taken_at ??= now();
            $score->save();

            // quiz_submitted → the assessment's educator (student emit rule, D5).
            $this->notifications->emit($student, 'quiz_submitted', $assessment->educator_id, [
                'assessment_id' => $assessment->id,
                'subject_id' => $assessment->subject_id,
                'section_id' => $assessment->section_id,
                'title' => 'A student submitted an assessment',
            ]);

            return $score;
        });
    }

    private function isCorrect(Quiz $quiz, string $given): bool
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
