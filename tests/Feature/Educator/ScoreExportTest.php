<?php

namespace Tests\Feature\Educator;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

// Task 27: export scores — ownership gate, preview counts, and bulk zip grouping/sanitization.
class ScoreExportTest extends TestCase
{
    use RefreshDatabase;

    private User $eduA;

    private User $eduB;

    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->eduA = $this->makeUser('educator');
        $this->eduB = $this->makeUser('educator');

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $this->term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);
    }

    public function test_export_and_preview_routes_are_ownership_gated(): void
    {
        [$assessment] = $this->assessmentWithRoster();

        $this->actingAs($this->eduB)->get(route('educator.scores.export.preview', $assessment))->assertForbidden();
        $this->actingAs($this->eduB)->get(route('educator.scores.export', $assessment))->assertForbidden();
    }

    public function test_preview_returns_enrolled_and_submission_counts(): void
    {
        [$assessment] = $this->assessmentWithRoster();

        $response = $this->actingAs($this->eduA)->get(route('educator.scores.export.preview', $assessment))->assertOk();

        $response->assertJson([
            'assessmentCode' => 'Q1', 'enrolled' => 2, 'withSubmission' => 1, 'withoutSubmission' => 1,
        ]);
    }

    public function test_single_export_downloads_a_styled_xlsx(): void
    {
        [$assessment] = $this->assessmentWithRoster();

        $response = $this->actingAs($this->eduA)->get(route('educator.scores.export', $assessment))->assertOk();

        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    public function test_bulk_export_requires_term_id_when_filtering_by_term(): void
    {
        $this->assessmentWithRoster();

        $this->actingAs($this->eduA)
            ->get(route('educator.scores.export-bulk', ['type' => 'term']))
            ->assertRedirect()->assertSessionHasErrors('termId');
    }

    public function test_bulk_export_by_term_produces_a_sanitized_zip_scoped_to_owner(): void
    {
        [$assessment] = $this->assessmentWithRoster();

        $response = $this->actingAs($this->eduA)
            ->get(route('educator.scores.export-bulk', ['type' => 'term', 'termId' => $this->term->id]))
            ->assertOk();

        $bytes = $response->streamedContent();
        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmp, $bytes);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);
        $this->assertSame(1, $zip->numFiles);

        $entry = $zip->getNameIndex(0);
        $this->assertStringStartsWith('PRELIM/', $entry);
        $this->assertStringEndsWith('.xlsx', $entry);
        $withoutExtension = preg_replace('/\.xlsx$/', '', $entry);
        $this->assertSame(strtoupper($withoutExtension), $withoutExtension); // path segments are uppercased

        $zip->close();
        @unlink($tmp);
    }

    public function test_bulk_export_never_includes_another_educators_assessments(): void
    {
        $this->assessmentWithRoster(); // eduA's data

        $subjectB = $this->subject($this->eduB);
        Assessment::create([
            'educator_id' => $this->eduB->id, 'subject_id' => $subjectB->id, 'section_id' => $subjectB->sections_id,
            'assessment_code' => 'ZZTOP', 'time_limit' => '30', 'term' => $this->term->id,
            'start_date' => '2026-07-01', 'end_date' => '2026-07-02', 'start_time' => '08:00', 'end_time' => '09:00',
        ]);

        $response = $this->actingAs($this->eduA)
            ->get(route('educator.scores.export-bulk', ['type' => 'all']))->assertOk();

        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($tmp, $response->streamedContent());
        $zip = new ZipArchive;
        $zip->open($tmp);
        $this->assertSame(1, $zip->numFiles); // only eduA's one group, never eduB's ZZTOP
        $zip->close();
        @unlink($tmp);
    }

    // ---- helpers ----

    /** @return array{0: Assessment} eduA's assessment with a 2-student roster: one submitted (passed), one not. */
    private function assessmentWithRoster(): array
    {
        $subject = $this->subject($this->eduA);
        $section = Section::find($subject->sections_id);
        $assessment = Assessment::create([
            'educator_id' => $this->eduA->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
            'assessment_code' => 'Q1', 'time_limit' => '30', 'term' => $this->term->id,
            'start_date' => '2026-07-01', 'end_date' => '2026-07-02', 'start_time' => '08:00', 'end_time' => '09:00',
        ]);
        Quiz::create([
            'assessment_id' => $assessment->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
            'educator_id' => $this->eduA->id, 'question' => '2+2', 'quiz_type' => 'multiple_choice',
            'choices' => ['A' => '3', 'B' => '4'], 'correct_answer' => 'B',
        ]);

        $submitted = $this->makeUser('student', ['surname' => 'Zamora', 'given_name' => 'Ann']);
        $notSubmitted = $this->makeUser('student', ['surname' => 'Cruz', 'given_name' => 'Bea']);
        Enrolled::create(['student_id' => $submitted->id, 'educator_id' => $this->eduA->id, 'subject_id' => $subject->id, 'is_active' => true]);
        Enrolled::create(['student_id' => $notSubmitted->id, 'educator_id' => $this->eduA->id, 'subject_id' => $subject->id, 'is_active' => true]);

        Score::create([
            'student_id' => $submitted->id, 'educator_id' => $this->eduA->id, 'assessment_id' => $assessment->id,
            'subject_id' => $subject->id, 'section_id' => $section->id, 'score' => 8, 'total_questions' => 10,
            'student_answer' => [], 'status' => 'passed', 'is_passed' => true, 'submitted_at' => now(),
        ]);

        return [$assessment];
    }

    private function makeUser(string $type, array $attrs = []): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now(), ...$attrs]);
        $user->roles()->attach(Role::where('name', $type)->value('id'));

        return $user;
    }

    private function section(User $edu, string $name = 'Section'): Section
    {
        $s = Section::create(['educator_id' => $edu->id, 'academic_term_id' => $this->term->id, 'section_name' => $name.uniqid(), 'is_active' => true]);
        $s->terms()->sync([$this->term->id]);

        return $s;
    }

    private function subject(User $edu): Subject
    {
        $section = $this->section($edu);

        return Subject::create(['educator_id' => $edu->id, 'sections_id' => $section->id, 'subject_code' => 'CS'.rand(100, 999), 'subject_name' => 'Info Management', 'is_active' => true]);
    }
}
