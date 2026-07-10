<?php

namespace Tests\Feature\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Role;
use App\Models\Section;
use App\Models\StudentAssessmentExemption;
use App\Models\Subject;
use App\Models\User;
use App\Services\AssessmentAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Task 01: same-instant-start timezone fix, is_active gate, exemption gate.
class AssessmentAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $educator;

    private User $student;

    private Assessment $assessment;

    private AssessmentAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }
        $this->educator = User::factory()->create(['user_type' => 'educator', 'email_verified_at' => now()]);
        $this->educator->roles()->attach(Role::where('name', 'educator')->value('id'));
        $this->student = User::factory()->create(['user_type' => 'student', 'email_verified_at' => now()]);
        $this->student->roles()->attach(Role::where('name', 'student')->value('id'));

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);
        $section = Section::create(['educator_id' => $this->educator->id, 'academic_term_id' => $term->id, 'section_name' => 'A1']);
        $subject = Subject::create(['educator_id' => $this->educator->id, 'sections_id' => $section->id, 'subject_code' => 'M1', 'subject_name' => 'Math']);

        // A student's local wall-clock "now" (Asia/Manila) — a start_date/start_time typed to
        // equal this instant must read as started, not "Starting Soon" (the bug this fixes).
        Carbon::setTestNow(Carbon::parse('2026-07-09 08:23:00', 'Asia/Manila'));

        $this->assessment = Assessment::create([
            'educator_id' => $this->educator->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
            'assessment_code' => 'Q1', 'time_limit' => '30', 'term' => $term->id, 'is_active' => true,
            'start_date' => '2026-07-09', 'end_date' => '2026-07-10',
            'start_time' => '08:23', 'end_time' => '17:00',
        ]);

        $this->service = app(AssessmentAvailabilityService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_assessment_starting_exactly_now_in_school_timezone_is_available_not_upcoming(): void
    {
        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Available', $summary['badge']);
        $this->assertTrue($summary['can_take']);
    }

    public function test_assessment_is_expired_at_the_exact_end_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 17:00:00', 'Asia/Manila'));

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Expired', $summary['badge']);
        $this->assertFalse($summary['can_take']);
        $this->assertFalse($summary['window_open']);
    }

    public function test_inactive_assessment_cannot_be_taken_even_inside_its_window(): void
    {
        $this->assessment->update(['is_active' => false]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Inactive', $summary['badge']);
        $this->assertFalse($summary['can_take']);
    }

    public function test_exempted_student_cannot_take_even_inside_the_window(): void
    {
        StudentAssessmentExemption::create([
            'educator_id' => $this->educator->id, 'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id, 'is_active' => true,
        ]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Exempted', $summary['badge']);
        $this->assertFalse($summary['can_take']);
    }

    public function test_inactive_exemption_does_not_block_the_student(): void
    {
        StudentAssessmentExemption::create([
            'educator_id' => $this->educator->id, 'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id, 'is_active' => false,
        ]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Available', $summary['badge']);
        $this->assertTrue($summary['can_take']);
    }
}
