<?php

namespace Tests\Unit\Services;

use App\Services\Export\ScoreRowBuilder;
use PHPUnit\Framework\TestCase;

// Task 27: ScoreRowBuilder is a pure function (no DB/FS access) — plain stdClass stand-ins for
// Enrolled/Score rows are enough, and this runs with zero framework bootstrap.
class ScoreRowBuilderTest extends TestCase
{
    private array $context = [
        'subject' => 'CS201', 'section' => 'BSCS21M1', 'assessmentCode' => 'Q1', 'academicTerm' => 'Prelim',
    ];

    public function test_zero_enrolled_produces_empty_rows_and_summary(): void
    {
        $result = ScoreRowBuilder::build([], [], 10, $this->context);

        $this->assertSame([], $result['rows']);
        $this->assertSame(['enrolled' => 0, 'withSubmission' => 0, 'withoutSubmission' => 0], $result['summary']);
    }

    public function test_zero_submissions_marks_every_student_no_submission(): void
    {
        $roster = [$this->enrolled(1, 'Zamora', 'Ann'), $this->enrolled(2, 'Cruz', 'Juan')];

        $result = ScoreRowBuilder::build($roster, [], 10, $this->context);

        $this->assertCount(2, $result['rows']);
        foreach ($result['rows'] as $row) {
            $this->assertSame('No Submission', $row['Status']);
            $this->assertSame('No Submission', $row['Remark']);
            $this->assertNull($row['Highest Score']);
            $this->assertNull($row['Percentage']);
            $this->assertNull($row['Highest Submitted At']);
        }
        $this->assertSame(['enrolled' => 2, 'withSubmission' => 0, 'withoutSubmission' => 2], $result['summary']);
    }

    public function test_multiple_retakes_export_highest_score_not_latest(): void
    {
        $roster = [$this->enrolled(1, 'Cruz', 'Juan')];
        $scores = [
            $this->score(id: 1, studentId: 1, score: 5, total: 10, passed: false),
            $this->score(id: 2, studentId: 1, score: 9, total: 10, passed: true), // highest, but not last inserted
            $this->score(id: 3, studentId: 1, score: 3, total: 10, passed: false), // latest by id, but lower score
        ];

        $result = ScoreRowBuilder::build($roster, $scores, 10, $this->context);

        $row = $result['rows'][0];
        $this->assertSame(9, $row['Highest Score']);
        $this->assertSame('Passed', $row['Status']);
        $this->assertSame(90.0, $row['Percentage']);
    }

    public function test_tied_scores_break_by_percentage_then_most_recent_id(): void
    {
        $roster = [$this->enrolled(1, 'Cruz', 'Juan')];
        $scores = [
            $this->score(id: 10, studentId: 1, score: 8, total: 10, passed: true), // 80%
            $this->score(id: 11, studentId: 1, score: 8, total: 8, passed: true),  // 100%, same raw score, higher %
        ];

        $result = ScoreRowBuilder::build($roster, $scores, 10, $this->context);

        // Same raw score (8) on both attempts — the one with the higher percentage (100% on an
        // 8-question attempt) wins over the 80% attempt.
        $this->assertSame(100.0, $result['rows'][0]['Percentage']);
        $this->assertSame(8, $result['rows'][0]['Total Questions']);
    }

    public function test_tied_score_and_percentage_break_by_most_recent_id(): void
    {
        $roster = [$this->enrolled(1, 'Cruz', 'Juan')];
        $scores = [
            $this->score(id: 20, studentId: 1, score: 8, total: 10, passed: true),
            $this->score(id: 21, studentId: 1, score: 8, total: 10, passed: true), // identical, higher id wins
        ];

        $result = ScoreRowBuilder::build($roster, $scores, 10, $this->context);

        // Both attempts are identical in score/percentage; the row must still resolve
        // deterministically (no crash, exactly one row emitted for the student).
        $this->assertCount(1, $result['rows']);
        $this->assertSame(8, $result['rows'][0]['Highest Score']);
    }

    public function test_total_questions_zero_or_missing_yields_null_percentage_without_throwing(): void
    {
        $roster = [$this->enrolled(1, 'Cruz', 'Juan'), $this->enrolled(2, 'Reyes', 'Liza')];
        $scores = [
            $this->score(id: 1, studentId: 1, score: 5, total: 0, passed: false),
            $this->score(id: 2, studentId: 2, score: 5, total: null, passed: false),
        ];

        $result = ScoreRowBuilder::build($roster, $scores, null, $this->context);

        foreach ($result['rows'] as $row) {
            $this->assertNull($row['Percentage']);
        }
    }

    public function test_null_score_on_submitted_row_is_a_data_anomaly(): void
    {
        $roster = [$this->enrolled(1, 'Cruz', 'Juan')];
        $scores = [$this->score(id: 1, studentId: 1, score: null, total: 10, passed: false)];

        $result = ScoreRowBuilder::build($roster, $scores, 10, $this->context);

        $row = $result['rows'][0];
        $this->assertSame('Data Anomaly', $row['Status']);
        $this->assertNull($row['Percentage']);
        $this->assertNull($row['Highest Score']);
    }

    public function test_rows_are_sorted_alphabetically_by_surname_then_given_name(): void
    {
        $roster = [
            $this->enrolled(1, 'Zamora', 'Ann'),
            $this->enrolled(2, 'Cruz', 'Bea'),
            $this->enrolled(3, 'Cruz', 'Ana'),
        ];

        $result = ScoreRowBuilder::build($roster, [], 10, $this->context);

        $names = array_column($result['rows'], 'Student Name');
        $this->assertSame(['Cruz, Ana', 'Cruz, Bea', 'Zamora, Ann'], $names);
    }

    private function enrolled(int $studentId, string $surname, string $givenName): object
    {
        $student = (object) ['id' => $studentId, 'surname' => $surname, 'given_name' => $givenName, 'user_id' => "STU{$studentId}"];

        return (object) ['student_id' => $studentId, 'student' => $student];
    }

    private function score(int $id, int $studentId, ?int $score, ?int $total, bool $passed): object
    {
        return (object) [
            'id' => $id, 'student_id' => $studentId, 'score' => $score, 'total_questions' => $total,
            'is_passed' => $passed, 'submitted_at' => '2026-07-01 10:00:00',
        ];
    }
}
