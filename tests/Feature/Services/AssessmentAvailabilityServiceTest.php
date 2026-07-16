<?php

namespace Tests\Feature\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Role;
use App\Models\Score;
use App\Models\Section;
use App\Models\StudentAssessmentAccess;
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

    public function test_expired_assessment_with_active_special_access_is_takeable_for_missed_student(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 17:00:00', 'Asia/Manila'));
        StudentAssessmentAccess::create([
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'is_active' => true,
        ]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Special Access', $summary['badge']);
        $this->assertTrue($summary['can_take']);
        $this->assertFalse($summary['window_open']);
    }

    public function test_inactive_special_access_does_not_reopen_expired_assessment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 17:00:00', 'Asia/Manila'));
        StudentAssessmentAccess::create([
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'is_active' => false,
        ]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Expired', $summary['badge']);
        $this->assertFalse($summary['can_take']);
    }

    public function test_special_access_does_not_bypass_exemption(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 17:00:00', 'Asia/Manila'));
        StudentAssessmentAccess::create([
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'is_active' => true,
        ]);
        StudentAssessmentExemption::create([
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'is_active' => true,
        ]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Exempted', $summary['badge']);
        $this->assertFalse($summary['can_take']);
    }

    public function test_special_access_is_consumed_after_first_submitted_attempt(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 17:00:00', 'Asia/Manila'));
        StudentAssessmentAccess::create([
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'is_active' => true,
        ]);
        Score::create([
            'student_id' => $this->student->id,
            'educator_id' => $this->educator->id,
            'assessment_id' => $this->assessment->id,
            'subject_id' => $this->assessment->subject_id,
            'section_id' => $this->assessment->section_id,
            'score' => 4,
            'total_questions' => 4,
            'student_answer' => [],
            'status' => 'passed',
            'is_passed' => true,
            'submitted_at' => now(),
        ]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Expired', $summary['badge']);
        $this->assertFalse($summary['can_take']);
    }

    public function test_special_access_allows_retake_when_granted_after_prior_submission(): void
    {
        Score::create([
            'student_id' => $this->student->id,
            'educator_id' => $this->educator->id,
            'assessment_id' => $this->assessment->id,
            'subject_id' => $this->assessment->subject_id,
            'section_id' => $this->assessment->section_id,
            'score' => 2,
            'total_questions' => 4,
            'student_answer' => [],
            'status' => 'failed',
            'is_passed' => false,
            'submitted_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-10 17:00:00', 'Asia/Manila'));
        StudentAssessmentAccess::create([
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'is_active' => true,
        ]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Special Access', $summary['badge']);
        $this->assertTrue($summary['can_take']);
        $this->assertSame(1, $summary['remaining']);
    }

    // Task 24: a grant can now carry an expires_at. Past it, the grant is dead even though the
    // student never used it.
    public function test_special_access_with_a_past_expiry_does_not_reopen_expired_assessment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 17:00:00', 'Asia/Manila'));
        StudentAssessmentAccess::create([
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'is_active' => true,
            'expires_at' => now()->copy()->subMinute(),
        ]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Expired', $summary['badge']);
        $this->assertFalse($summary['can_take']);
    }

    public function test_special_access_with_a_future_expiry_is_takeable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 17:00:00', 'Asia/Manila'));
        StudentAssessmentAccess::create([
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'is_active' => true,
            'expires_at' => now()->copy()->addHours(24),
        ]);

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Special Access', $summary['badge']);
        $this->assertTrue($summary['can_take']);
    }

    // Regression guard for every grant issued before the duration was configurable.
    public function test_special_access_with_a_null_expiry_never_times_out(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 17:00:00', 'Asia/Manila'));
        StudentAssessmentAccess::create([
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'is_active' => true,
            'expires_at' => null,
        ]);

        Carbon::setTestNow(Carbon::parse('2027-07-10 17:00:00', 'Asia/Manila'));

        $summary = $this->service->summarize($this->assessment, $this->student->id);

        $this->assertSame('Special Access', $summary['badge']);
        $this->assertTrue($summary['can_take']);
    }
}
