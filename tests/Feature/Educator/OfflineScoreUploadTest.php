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
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OfflineScoreUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $educator;

    private Assessment $assessment;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->educator = $this->makeUser('educator');
        $this->student = $this->makeUser('student', ['user_id' => '2026-12345']);

        $year = AcademicYear::create(['year' => '2026 - 2027']);
        $term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);
        $section = Section::create(['educator_id' => $this->educator->id, 'academic_term_id' => $term->id, 'section_name' => 'A1']);
        $section->terms()->sync([$term->id]);
        $subject = Subject::create([
            'educator_id' => $this->educator->id,
            'sections_id' => $section->id,
            'subject_code' => 'M1',
            'subject_name' => 'Math',
        ]);
        $this->assessment = Assessment::create([
            'educator_id' => $this->educator->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'assessment_code' => 'Q1',
            'time_limit' => '30',
            'term' => $term->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-02',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
        Quiz::create([
            'assessment_id' => $this->assessment->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'educator_id' => $this->educator->id,
            'question' => '2+2',
            'quiz_type' => 'multiple_choice',
            'choices' => ['A' => '3', 'B' => '4'],
            'correct_answer' => 'B',
        ]);
        Enrolled::create([
            'student_id' => $this->student->id,
            'educator_id' => $this->educator->id,
            'subject_id' => $subject->id,
            'is_active' => true,
        ]);
    }

    public function test_educator_uploads_valid_offline_scores(): void
    {
        $this->actingAs($this->educator)
            ->post(route('educator.scores.upload'), [
                'assessment_uuid' => $this->assessment->uuid,
                'file' => $this->csv([
                    ['2026-12345', 1, 1],
                ]),
            ])
            ->assertRedirect(route('educator.scores.index'));

        $score = Score::firstOrFail();
        $this->assertSame($this->student->id, $score->student_id);
        $this->assertSame($this->educator->id, $score->educator_id);
        $this->assertTrue($score->is_passed);
        $this->assertSame('passed', $score->status);
    }

    public function test_invalid_student_rejects_entire_batch(): void
    {
        $this->actingAs($this->educator)
            ->post(route('educator.scores.upload'), [
                'assessment_uuid' => $this->assessment->uuid,
                'file' => $this->csv([
                    ['2026-12345', 1, 1],
                    ['2026-99999', 1, 1],
                ]),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Score::count());
    }

    public function test_wrong_educator_assessment_is_rejected(): void
    {
        $otherEducator = $this->makeUser('educator');

        $this->actingAs($otherEducator)
            ->post(route('educator.scores.upload'), [
                'assessment_uuid' => $this->assessment->uuid,
                'file' => $this->csv([
                    ['2026-12345', 1, 1],
                ]),
            ])
            ->assertNotFound();

        $this->assertSame(0, Score::count());
    }

    public function test_score_anomaly_rejects_entire_batch(): void
    {
        $this->actingAs($this->educator)
            ->post(route('educator.scores.upload'), [
                'assessment_uuid' => $this->assessment->uuid,
                'file' => $this->csv([
                    ['2026-12345', 2, 1],
                ]),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Score::count());
    }

    public function test_duplicate_existing_submission_is_rejected(): void
    {
        Score::create([
            'student_id' => $this->student->id,
            'educator_id' => $this->educator->id,
            'assessment_id' => $this->assessment->id,
            'subject_id' => $this->assessment->subject_id,
            'section_id' => $this->assessment->section_id,
            'score' => 1,
            'total_questions' => 1,
            'student_answer' => [],
            'status' => 'passed',
            'is_passed' => true,
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->educator)
            ->post(route('educator.scores.upload'), [
                'assessment_uuid' => $this->assessment->uuid,
                'file' => $this->csv([
                    ['2026-12345', 1, 1],
                ]),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(1, Score::count());
    }

    private function csv(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'scores');
        $handle = fopen($path, 'w');
        fputcsv($handle, ['student_id', 'score', 'total_questions']);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return new UploadedFile($path, 'scores.csv', 'text/csv', null, true);
    }

    private function makeUser(string $type, array $attrs = []): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now(), ...$attrs]);
        $user->roles()->attach(Role::where('name', $type)->value('id'));

        return $user;
    }
}
