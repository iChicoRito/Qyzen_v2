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

// Stage G: educator feature tests. The critical concern is the OWNERSHIP GATE — educator A must
// never see or mutate educator B's data (no RLS; the visibleTo scope + policies are the only guard).
class EducatorFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $eduA;
    private User $eduB;
    private User $student;
    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }
        // Section/Subject policies are permission-gated for educators (Stage D) — grant them.
        $this->seedEducatorPermissions(['sections:view', 'sections:create', 'sections:update', 'sections:delete',
            'subjects:view', 'subjects:create', 'subjects:update', 'subjects:delete']);

        $this->eduA = $this->makeUser('educator', 'educator');
        $this->eduB = $this->makeUser('educator', 'educator');
        $this->student = $this->makeUser('student', 'student');

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $this->term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);
    }

    // ---- gate ----

    public function test_non_educator_is_bounced_from_educator_routes(): void
    {
        foreach (['educator.dashboard', 'educator.sections.index', 'educator.assessments.index', 'educator.scores.index'] as $route) {
            $this->actingAs($this->student)->get(route($route))->assertStatus(302);
        }
    }

    public function test_educator_can_view_own_lists(): void
    {
        foreach (['educator.dashboard', 'educator.sections.index', 'educator.subjects.index', 'educator.enrollment.index', 'educator.assessments.index', 'educator.quizzes.index', 'educator.scores.index', 'educator.materials.index', 'educator.chats.index', 'educator.monitoring.index'] as $route) {
            $this->actingAs($this->eduA)->get(route($route))->assertOk();
        }
    }

    // ---- G2 sections (ownership + name-per-term uniqueness) ----

    public function test_educator_creates_section_and_cannot_edit_others(): void
    {
        $this->actingAs($this->eduA)->post(route('educator.sections.store'), [
            'section_name' => 'A1', 'academic_term_ids' => [$this->term->id], 'is_active' => '1',
        ])->assertRedirect(route('educator.sections.index'));

        $section = Section::where('section_name', 'A1')->firstOrFail();
        $this->assertSame($this->eduA->id, $section->educator_id);

        // eduB cannot edit eduA's section (policy denies → 403)
        $this->actingAs($this->eduB)->get(route('educator.sections.edit', $section))->assertForbidden();
        $this->actingAs($this->eduB)->delete(route('educator.sections.destroy', $section))->assertForbidden();
    }

    public function test_section_name_unique_per_term_per_educator(): void
    {
        $this->actingAs($this->eduA)->post(route('educator.sections.store'), [
            'section_name' => 'A1', 'academic_term_ids' => [$this->term->id], 'is_active' => '1',
        ])->assertRedirect();

        $this->actingAs($this->eduA)->post(route('educator.sections.store'), [
            'section_name' => 'A1', 'academic_term_ids' => [$this->term->id], 'is_active' => '1',
        ])->assertSessionHasErrors('section_name');

        // but eduB may reuse the same name (scoped per educator)
        $this->actingAs($this->eduB)->post(route('educator.sections.store'), [
            'section_name' => 'A1', 'academic_term_ids' => [$this->term->id], 'is_active' => '1',
        ])->assertRedirect();
    }

    // ---- G3 subjects (one row per section) ----

    public function test_subject_creates_one_row_per_section(): void
    {
        $s1 = $this->section($this->eduA, 'S1');
        $s2 = $this->section($this->eduA, 'S2');

        $this->actingAs($this->eduA)->post(route('educator.subjects.store'), [
            'subject_code' => 'MATH101', 'subject_name' => 'Math', 'section_ids' => [$s1->id, $s2->id], 'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(2, Subject::where('subject_code', 'MATH101')->count());
    }

    // ---- G4 enrollment + scope ----

    public function test_enrollment_pairs_and_scope(): void
    {
        $subject = $this->subject($this->eduA);

        $this->actingAs($this->eduA)->post(route('educator.enrollment.store'), [
            'student_ids' => [$this->student->id], 'subject_ids' => [$subject->id], 'is_active' => '1',
        ])->assertRedirect();

        $enr = Enrolled::firstOrFail();
        $this->assertSame($this->eduA->id, $enr->educator_id);
        // eduB cannot see eduA's enrollment via the scope
        $this->assertFalse(Enrolled::visibleTo($this->eduB)->whereKey($enr->id)->exists());
    }

    // ---- G5 assessments: publish-on-activate notifies enrolled students ----

    public function test_activating_assessment_notifies_enrolled_students(): void
    {
        $subject = $this->subject($this->eduA);
        $section = Section::find($subject->sections_id);
        Enrolled::create(['student_id' => $this->student->id, 'educator_id' => $this->eduA->id, 'subject_id' => $subject->id, 'is_active' => true]);

        // create inactive (no notification yet)
        $this->actingAs($this->eduA)->post(route('educator.assessments.store'), $this->assessmentPayload($subject, $section, false))->assertRedirect();
        $this->assertDatabaseCount('tbl_notifications', 0);

        // flip to active → publish + notify enrolled student
        $assessment = Assessment::firstOrFail();
        $this->actingAs($this->eduA)->put(route('educator.assessments.update', $assessment), $this->assessmentPayload($subject, $section, true))->assertRedirect();
        $this->assertDatabaseHas('tbl_notifications', [
            'recipient_user_id' => $this->student->id, 'event_type' => 'assessment_created',
        ]);
    }

    // ---- G6 quizzes: correct_answer never serialized ----

    public function test_quiz_correct_answer_is_hidden_in_serialization(): void
    {
        $subject = $this->subject($this->eduA);
        $assessment = Assessment::create($this->assessmentModelData($subject));
        $quiz = Quiz::create([
            'assessment_id' => $assessment->id, 'subject_id' => $subject->id, 'section_id' => $subject->sections_id,
            'educator_id' => $this->eduA->id, 'question' => '2+2', 'quiz_type' => 'multiple_choice',
            'choices' => ['A' => '3', 'B' => '4'], 'correct_answer' => 'B',
        ]);

        $this->assertArrayNotHasKey('correct_answer', $quiz->toArray());
        // but readable server-side
        $this->assertSame('B', $quiz->correct_answer);
    }

    // ---- helpers ----

    private function makeUser(string $type, string $roleName): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $roleName)->value('id'));

        return $user;
    }

    private function seedEducatorPermissions(array $strings): void
    {
        $eduRole = Role::where('name', 'educator')->first();
        foreach ($strings as $s) {
            [$resource, $action] = explode(':', $s);
            $perm = \App\Models\Permission::create([
                'name' => $s, 'resource' => $resource, 'action' => $action,
                'description' => $s, 'module' => $resource, 'is_active' => true, 'permission_string' => $s,
            ]);
            $eduRole->permissions()->attach($perm->id);
        }
    }

    private function section(User $edu, string $name): Section
    {
        $s = Section::create(['educator_id' => $edu->id, 'academic_term_id' => $this->term->id, 'section_name' => $name, 'is_active' => true]);
        $s->terms()->sync([$this->term->id]);

        return $s;
    }

    private function subject(User $edu): Subject
    {
        $section = $this->section($edu, 'Sec'.uniqid());

        return Subject::create(['educator_id' => $edu->id, 'sections_id' => $section->id, 'subject_code' => 'C'.rand(100, 999), 'subject_name' => 'Subj', 'is_active' => true]);
    }

    private function assessmentPayload(Subject $subject, Section $section, bool $active): array
    {
        return [
            'assessment_code' => 'Q1', 'subject_id' => $subject->id, 'section_id' => $section->id, 'term' => $this->term->id,
            'time_limit' => '30', 'cheating_attempts' => 0, 'is_shuffle' => '0', 'allow_review' => '0',
            'allow_retake' => '0', 'retake_count' => 0, 'allow_hint' => '0', 'hint_count' => 0,
            'is_active' => $active ? '1' : '0',
            'start_date' => '2026-07-01', 'end_date' => '2026-07-02', 'start_time' => '08:00', 'end_time' => '09:00',
        ];
    }

    private function assessmentModelData(Subject $subject): array
    {
        return [
            'educator_id' => $this->eduA->id, 'subject_id' => $subject->id, 'section_id' => $subject->sections_id,
            'assessment_code' => 'Q1', 'time_limit' => '30', 'term' => $this->term->id,
            'start_date' => '2026-07-01', 'end_date' => '2026-07-02', 'start_time' => '08:00', 'end_time' => '09:00',
        ];
    }
}
