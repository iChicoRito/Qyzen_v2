<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Role;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Role dashboards: each loads for its role and metrics are ownership-scoped (Stage D leak check).
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $eduA;

    private User $eduB;

    private User $studentA; // enrolled with eduA

    private User $studentB; // enrolled with eduB

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->admin = $this->makeUser('admin', 'admin');
        $this->eduA = $this->makeUser('educator', 'educator');
        $this->eduB = $this->makeUser('educator', 'educator');
        $this->studentA = $this->makeUser('student', 'student');
        $this->studentB = $this->makeUser('student', 'student');

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);

        // Two independent educator tenants.
        [$secA, $subA, $assessA] = $this->makeTenant($this->eduA, $term->id, 'A');
        [$secB, $subB, $assessB] = $this->makeTenant($this->eduB, $term->id, 'B');

        Enrolled::create(['student_id' => $this->studentA->id, 'educator_id' => $this->eduA->id, 'subject_id' => $subA->id, 'is_active' => true]);
        Enrolled::create(['student_id' => $this->studentB->id, 'educator_id' => $this->eduB->id, 'subject_id' => $subB->id, 'is_active' => true]);

        // studentA scored 80%, studentB scored 40% — used to prove averages don't cross tenants.
        $this->makeScore($this->studentA, $this->eduA, $assessA, $subA, $secA, 80);
        $this->makeScore($this->studentB, $this->eduB, $assessB, $subB, $secB, 40);
    }

    public function test_educator_dashboard_is_scoped_to_owner(): void
    {
        $res = $this->actingAs($this->eduA)->get(route('educator.dashboard'));

        $res->assertOk();
        // eduA owns exactly one section / subject / student — eduB's are not counted.
        $this->assertSame(1, $res->viewData('sectionCount'));
        $this->assertSame(1, $res->viewData('subjectCount'));
        $this->assertSame(1, $res->viewData('studentCount'));
    }

    public function test_student_dashboard_shows_only_own_scores(): void
    {
        $res = $this->actingAs($this->studentA)->get(route('student.dashboard'));

        $res->assertOk();
        // studentA sees only their own 80% — never studentB's 40%.
        $this->assertEqualsWithDelta(80.0, (float) $res->viewData('overallAvg'), 0.01);
        $this->assertSame(1, $res->viewData('subjectCount'));
    }

    public function test_admin_dashboard_sees_institution_wide_totals(): void
    {
        $res = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $res->assertOk();
        $this->assertSame(2, $res->viewData('educatorCount'));
        $this->assertSame(2, $res->viewData('studentCount'));
        $this->assertSame(2, $res->viewData('sectionCount'));
        // system avg spans both tenants: (80 + 40) / 2 = 60.
        $this->assertEqualsWithDelta(60.0, (float) $res->viewData('systemAvg'), 0.01);
    }

    public function test_calendar_page_loads_and_is_scoped_per_role(): void
    {
        // Each portal's /calendar reuses Assessment::visibleTo, so events never cross tenants.
        $this->actingAs($this->eduA)->get(route('educator.calendar'))->assertOk();
        $this->actingAs($this->studentA)->get(route('student.calendar'))->assertOk();

        $admin = $this->actingAs($this->admin)->get(route('admin.calendar'));
        $admin->assertOk();
        // Admin sees both tenants' assessments; an educator sees only their own one.
        $this->assertCount(2, $admin->viewData('events'));
        $eduEvents = $this->actingAs($this->eduA)->get(route('educator.calendar'))->viewData('events');
        $this->assertCount(1, $eduEvents);
    }

    public function test_calendar_event_detail_fragment_is_scoped_and_bare(): void
    {
        $assessment = Assessment::where('educator_id', $this->eduA->id)->firstOrFail();

        // Owner educator: bare fragment under ?modal=1 (no chrome), with the reusable dismiss control.
        $frag = $this->actingAs($this->eduA)->get(route('calendar.assessment', ['assessment' => $assessment, 'modal' => 1]));
        $frag->assertOk();
        $this->assertStringNotContainsString('id="sidebar"', $frag->getContent());
        $this->assertStringContainsString('data-modal-cancel', $frag->getContent());
        $this->assertStringContainsString($assessment->assessment_code, $frag->getContent());

        // Another educator can't see it (mirrors the calendar's own visibility scope).
        $this->actingAs($this->eduB)->get(route('calendar.assessment', ['assessment' => $assessment]))->assertNotFound();
    }

    public function test_student_cannot_load_admin_dashboard(): void
    {
        // RequireRole bounces a wrong-role user away (redirect) rather than rendering the admin view.
        $res = $this->actingAs($this->studentA)->get(route('admin.dashboard'));
        $res->assertRedirect();
        $this->assertNotSame(200, $res->getStatusCode());
    }

    // ---- helpers ----

    private function makeUser(string $type, string $roleName): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $roleName)->value('id'));

        return $user;
    }

    /** @return array{0: Section, 1: Subject, 2: Assessment} */
    private function makeTenant(User $educator, int $termId, string $tag): array
    {
        $section = Section::create(['educator_id' => $educator->id, 'academic_term_id' => $termId, 'section_name' => "Sec-$tag"]);
        $subject = Subject::create(['educator_id' => $educator->id, 'sections_id' => $section->id, 'subject_code' => "C-$tag", 'subject_name' => "Subj-$tag"]);
        $assessment = Assessment::create([
            'educator_id' => $educator->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
            'assessment_code' => "Q-$tag", 'time_limit' => '30', 'term' => $termId,
            'start_date' => '2026-07-01', 'end_date' => '2026-07-31', 'start_time' => '08:00', 'end_time' => '09:00',
        ]);

        return [$section, $subject, $assessment];
    }

    private function makeScore(User $student, User $educator, Assessment $a, Subject $subject, Section $section, int $pct): void
    {
        Score::create([
            'student_id' => $student->id, 'educator_id' => $educator->id, 'assessment_id' => $a->id,
            'subject_id' => $subject->id, 'section_id' => $section->id,
            'score' => $pct, 'total_questions' => 100, 'student_answer' => [],
            'status' => 'submitted', 'is_passed' => $pct >= 75, 'taken_at' => now(),
        ]);
    }
}
