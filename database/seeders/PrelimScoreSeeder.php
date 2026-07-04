<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Score;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Demo data: every enrolled student gets a submitted score (with realistic per-question
// answers) for every assessment in the "Prelim" term, so the Download Grades export has
// realistic With/No Submission mixes instead of near-empty rosters, and the notification
// system shows real quiz_submitted entries for the educator. Depends on BulkClassSeeder's
// students/subjects/sections/assessments/quizzes.
// Run standalone: php artisan db:seed --class=PrelimScoreSeeder
class PrelimScoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BulkClassSeeder::class);

        $assessments = Assessment::whereHas('academicTerm', fn ($q) => $q->where('term_name', 'Prelim'))->get();
        $notifications = app(NotificationService::class);

        $created = 0;
        DB::transaction(function () use ($assessments, $notifications, &$created) {
            foreach ($assessments as $assessment) {
                $roster = Enrolled::with('student')->where('subject_id', $assessment->subject_id)->where('is_active', true)->get();
                $quizzes = $assessment->quizzes()->get(['id', 'correct_answer']);

                foreach ($roster as $enrolled) {
                    $score = Score::firstOrCreate(
                        ['student_id' => $enrolled->student_id, 'assessment_id' => $assessment->id],
                        $this->attempt($assessment, $enrolled->student_id, $quizzes),
                    );
                    if ($score->wasRecentlyCreated) {
                        $created++;
                        $notifications->emit($enrolled->student, 'quiz_submitted', $assessment->educator_id, [
                            'assessment_id' => $assessment->id,
                            'subject_id' => $assessment->subject_id,
                            'section_id' => $assessment->section_id,
                            'title' => 'A student submitted an assessment',
                            'link_path' => route('educator.scores.index'),
                        ]);
                    }
                }
            }
        });

        $this->command?->info("PrelimScoreSeeder: {$created} scores created across {$assessments->count()} Prelim assessments (total scores now ".Score::count().').');
    }

    private function attempt(Assessment $assessment, int $studentId, Collection $quizzes): array
    {
        $total = max($quizzes->count(), 1);
        $correctCount = rand((int) ceil($total * 0.4), $total); // 40%-100% spread — a natural Passed/Failed mix
        $isPassed = ($correctCount / $total) >= 0.75;
        $submittedAt = now()->subDays(rand(0, 3))->subMinutes(rand(0, 600));

        $answers = [];
        foreach ($quizzes as $qi => $quiz) {
            $answers[$quiz->id] = $qi < $correctCount ? $quiz->correct_answer : 'wrong';
        }

        return [
            'educator_id' => $assessment->educator_id,
            'subject_id' => $assessment->subject_id,
            'section_id' => $assessment->section_id,
            'score' => $correctCount,
            'total_questions' => $total,
            'student_answer' => $answers,
            'warning_attempts' => 0,
            'status' => $isPassed ? 'passed' : 'failed',
            'is_passed' => $isPassed,
            'taken_at' => $submittedAt->copy()->subMinutes(rand(5, 25)),
            'submitted_at' => $submittedAt,
        ];
    }
}
