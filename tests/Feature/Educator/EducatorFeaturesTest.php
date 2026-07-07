<?php

namespace Tests\Feature\Educator;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Permission;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
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

    // J4: cross-tenant re-audit — an educator must not reach another educator's assessment/quiz
    // edit pages through the built routes (not just at the model-scope layer).
    public function test_educator_cannot_reach_another_educators_assessment_or_quiz(): void
    {
        $subject = $this->subject($this->eduA);
        $assessment = Assessment::create($this->assessmentModelData($subject));
        $quiz = Quiz::create([
            'assessment_id' => $assessment->id, 'subject_id' => $subject->id, 'section_id' => $subject->sections_id,
            'educator_id' => $this->eduA->id, 'question' => '2+2', 'quiz_type' => 'multiple_choice',
            'choices' => ['A' => '3', 'B' => '4'], 'correct_answer' => 'B',
        ]);

        $this->actingAs($this->eduB)->get(route('educator.assessments.edit', $assessment))->assertForbidden();
        $this->actingAs($this->eduB)->delete(route('educator.assessments.destroy', $assessment))->assertForbidden();
        $this->actingAs($this->eduB)->get(route('educator.quizzes.edit', $quiz))->assertForbidden();
        $this->actingAs($this->eduB)->delete(route('educator.quizzes.destroy', $quiz))->assertForbidden();
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

    public function test_creating_assessment_with_multiple_subjects_makes_one_each(): void
    {
        $subjectA = $this->subject($this->eduA);
        $subjectB = $this->subject($this->eduA);

        $payload = $this->assessmentPayload($subjectA, Section::find($subjectA->sections_id), false);
        $payload['subject_ids'] = [$subjectA->id, $subjectB->id];

        $this->actingAs($this->eduA)->post(route('educator.assessments.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('tbl_assessments', ['subject_id' => $subjectA->id, 'section_id' => $subjectA->sections_id]);
        $this->assertDatabaseHas('tbl_assessments', ['subject_id' => $subjectB->id, 'section_id' => $subjectB->sections_id]);
    }

    public function test_assessments_index_sorts_by_subject_on_the_server(): void
    {
        $alpha = Subject::create([
            'educator_id' => $this->eduA->id,
            'sections_id' => $this->section($this->eduA, 'Alpha Section')->id,
            'subject_code' => 'A100',
            'subject_name' => 'Alpha Subject',
            'is_active' => true,
        ]);
        $zulu = Subject::create([
            'educator_id' => $this->eduA->id,
            'sections_id' => $this->section($this->eduA, 'Zulu Section')->id,
            'subject_code' => 'Z100',
            'subject_name' => 'Zulu Subject',
            'is_active' => true,
        ]);
        Assessment::create($this->assessmentModelData($zulu));
        Assessment::create(array_merge($this->assessmentModelData($alpha), ['assessment_code' => 'A2']));

        $this->actingAs($this->eduA)
            ->get(route('educator.assessments.index', ['sort' => 'subject', 'direction' => 'asc']))
            ->assertOk()
            ->assertSee('data-sort="subject"', false)
            ->assertSeeInOrder(['Alpha Subject', 'Zulu Subject']);
    }

    public function test_editing_assessment_can_add_extra_subjects(): void
    {
        $subjectA = $this->subject($this->eduA);
        $subjectB = $this->subject($this->eduA);
        $assessment = Assessment::create($this->assessmentModelData($subjectA));

        // Edit: keep current subject (A, via hidden) + add B.
        $payload = $this->assessmentPayload($subjectA, Section::find($subjectA->sections_id), false);
        $payload['subject_ids'] = [$subjectA->id, $subjectB->id];

        $this->actingAs($this->eduA)->put(route('educator.assessments.update', $assessment), $payload)->assertRedirect();

        // A updated in place (still one row), B added as a new assessment.
        $this->assertEquals(1, Assessment::where('subject_id', $subjectA->id)->count());
        $this->assertDatabaseHas('tbl_assessments', ['subject_id' => $subjectB->id, 'section_id' => $subjectB->sections_id]);
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

    public function test_store_mc_quiz_uses_radio_key_as_correct_answer(): void
    {
        $subject = $this->subject($this->eduA);
        $assessment = Assessment::create($this->assessmentModelData($subject));

        $this->actingAs($this->eduA)->post(route('educator.quizzes.store'), [
            'assessment_ids' => [$assessment->id], 'question' => '2+2?', 'quiz_type' => 'multiple_choice',
            'choices' => ['A' => '3', 'B' => '4', 'C' => '', 'D' => ''], 'correct_answer' => 'B',
        ])->assertRedirect();

        $quiz = Quiz::firstOrFail();
        $this->assertSame('B', $quiz->correct_answer);
        $this->assertSame('3', $quiz->choices['A']);
        $this->assertSame('4', $quiz->choices['B']);
    }

    public function test_store_identification_quiz_accepts_multiple_answers(): void
    {
        $subject = $this->subject($this->eduA);
        $assessment = Assessment::create($this->assessmentModelData($subject));

        // one answer → stored plain
        $this->actingAs($this->eduA)->post(route('educator.quizzes.store'), [
            'assessment_ids' => [$assessment->id], 'question' => 'Capital of PH?', 'quiz_type' => 'identification',
            'answers' => ['Manila'],
        ])->assertRedirect();
        $this->assertSame('Manila', Quiz::latest('id')->first()->correct_answer);

        // multiple answers → stored as JSON array
        $this->actingAs($this->eduA)->post(route('educator.quizzes.store'), [
            'assessment_ids' => [$assessment->id], 'question' => 'A primary color?', 'quiz_type' => 'identification',
            'answers' => ['red', 'blue', ''],
        ])->assertRedirect();
        $this->assertSame(['red', 'blue'], json_decode(Quiz::latest('id')->first()->correct_answer, true));
    }

    public function test_store_quiz_adds_question_to_multiple_assessments(): void
    {
        $subject = $this->subject($this->eduA);
        $a1 = Assessment::create($this->assessmentModelData($subject));
        $a2 = Assessment::create(['assessment_code' => 'Q2'] + $this->assessmentModelData($subject));

        $this->actingAs($this->eduA)->post(route('educator.quizzes.store'), [
            'assessment_ids' => [$a1->id, $a2->id], 'question' => 'Shared?', 'quiz_type' => 'identification',
            'answers' => ['yes'],
        ])->assertRedirect();

        $this->assertDatabaseHas('tbl_quizzes', ['assessment_id' => $a1->id, 'question' => 'Shared?']);
        $this->assertDatabaseHas('tbl_quizzes', ['assessment_id' => $a2->id, 'question' => 'Shared?']);
    }

    public function test_bulk_upload_imports_each_file_into_every_selected_assessment(): void
    {
        $subject = $this->subject($this->eduA);
        $a1 = Assessment::create($this->assessmentModelData($subject));
        $a2 = Assessment::create(['assessment_code' => 'Q2'] + $this->assessmentModelData($subject));

        $csv = "question,quiz_type,choice_a,choice_b,choice_c,choice_d,correct_answer\n"
            ."2+2?,multiple_choice,3,4,5,6,B\n"
            ."Capital of PH?,identification,,,,,Manila\n";
        $file = File::createWithContent('quiz.csv', $csv);

        $this->actingAs($this->eduA)->post(route('educator.quizzes.upload'), [
            'assessment_ids' => [$a1->id, $a2->id],
            'files' => [$file],
        ])->assertRedirect();

        // 2 valid rows × 2 assessments = 4 quizzes, split evenly.
        $this->assertSame(2, Quiz::where('assessment_id', $a1->id)->count());
        $this->assertSame(2, Quiz::where('assessment_id', $a2->id)->count());
    }

    public function test_bulk_upload_is_all_or_nothing_when_any_row_is_invalid(): void
    {
        $subject = $this->subject($this->eduA);
        $assessment = Assessment::create($this->assessmentModelData($subject));

        // Row 2 is valid, row 3 is invalid (bad quiz_type) → whole upload must be rejected.
        $csv = "question,quiz_type,choice_a,choice_b,choice_c,choice_d,correct_answer\n"
            ."2+2?,multiple_choice,3,4,5,6,B\n"
            ."Broken?,nonsense,,,,,X\n";
        $file = File::createWithContent('quiz.csv', $csv);

        $this->actingAs($this->eduA)->post(route('educator.quizzes.upload'), [
            'assessment_ids' => [$assessment->id],
            'files' => [$file],
        ])->assertSessionHasErrors('files');

        // Nothing saved — not even the valid row.
        $this->assertSame(0, Quiz::where('assessment_id', $assessment->id)->count());
    }

    public function test_bulk_upload_rejects_wrong_file_type_with_format_message(): void
    {
        $subject = $this->subject($this->eduA);
        $assessment = Assessment::create($this->assessmentModelData($subject));

        $bad = File::createWithContent('notes.pdf', '%PDF-1.4 fake');

        $this->actingAs($this->eduA)->post(route('educator.quizzes.upload'), [
            'assessment_ids' => [$assessment->id],
            'files' => [$bad],
        ])->assertSessionHasErrors('files.0');

        $this->assertSame(0, Quiz::where('assessment_id', $assessment->id)->count());
    }

    public function test_bulk_upload_rejects_right_filetype_with_wrong_columns(): void
    {
        $subject = $this->subject($this->eduA);
        $assessment = Assessment::create($this->assessmentModelData($subject));

        // A real CSV, but not the template — missing required columns.
        $csv = "name,email\nJuan,juan@example.com\n";
        $file = File::createWithContent('contacts.csv', $csv);

        $response = $this->actingAs($this->eduA)->post(route('educator.quizzes.upload'), [
            'assessment_ids' => [$assessment->id],
            'files' => [$file],
        ])->assertSessionHasErrors('files');

        $this->assertStringContainsString('missing required column', collect(
            session('errors')->get('files'))->implode(' '));
        $this->assertSame(0, Quiz::where('assessment_id', $assessment->id)->count());
    }

    // ---- G7 scores: task 26 organization (student column + filters, owner-scoped) ----

    public function test_scores_index_shows_student_columns_filters_and_stays_scoped(): void
    {
        $subject = $this->subject($this->eduA);
        $section = Section::find($subject->sections_id);
        $this->student->update(['given_name' => 'Ann', 'surname' => 'Zamora']);
        $assessment = Assessment::create($this->assessmentModelData($subject));
        Score::create([
            'student_id' => $this->student->id, 'educator_id' => $this->eduA->id, 'assessment_id' => $assessment->id,
            'subject_id' => $subject->id, 'section_id' => $section->id, 'score' => 8, 'total_questions' => 10,
            'student_answer' => [], 'status' => 'passed', 'is_passed' => true, 'submitted_at' => now(),
        ]);
        $otherOwnStudent = $this->makeUser('student', 'student');
        $otherOwnStudent->update(['given_name' => 'Ben', 'surname' => 'Alpha']);
        Score::create([
            'student_id' => $otherOwnStudent->id, 'educator_id' => $this->eduA->id, 'assessment_id' => $assessment->id,
            'subject_id' => $subject->id, 'section_id' => $section->id, 'score' => 6, 'total_questions' => 10,
            'student_answer' => [], 'status' => 'failed', 'is_passed' => false, 'submitted_at' => now(),
        ]);

        // eduB's score must never leak into eduA's view (visibleTo).
        $subjectB = $this->subject($this->eduB);
        $assessmentB = Assessment::create([
            'educator_id' => $this->eduB->id, 'subject_id' => $subjectB->id, 'section_id' => $subjectB->sections_id,
            'assessment_code' => 'ZZTOP', 'time_limit' => '30', 'term' => $this->term->id,
            'start_date' => '2026-07-01', 'end_date' => '2026-07-02', 'start_time' => '08:00', 'end_time' => '09:00',
        ]);
        Score::create([
            'student_id' => $this->student->id, 'educator_id' => $this->eduB->id, 'assessment_id' => $assessmentB->id,
            'subject_id' => $subjectB->id, 'section_id' => $subjectB->sections_id, 'score' => 1, 'total_questions' => 10,
            'student_answer' => [], 'status' => 'failed', 'is_passed' => false, 'submitted_at' => now(),
        ]);

        $res = $this->actingAs($this->eduA)->get(route('educator.scores.index', [
            'search' => 'Zamora',
            'subject' => $subject->id,
            'per_page' => 10,
        ]))->assertOk();

        $res->assertSee('Zamora')->assertSee('Ann');                 // student-info first column
        $res->assertSee($subject->subject_code)->assertSee($section->section_name); // subject/section columns
        $res->assertSee('data-filter="subject"', false)              // new filter selects present
            ->assertSee('data-filter="section"', false)
            ->assertSee('data-filter="term"', false);
        $res->assertDontSee('Alpha');                                // server-side search, not client hiding
        $res->assertDontSee('ZZTOP');                                // eduB's assessment never shown
    }

    public function test_sections_index_does_not_nest_modal_or_row_forms_inside_query_controls(): void
    {
        $this->section($this->eduA, 'Alpha');

        $response = $this->actingAs($this->eduA)
            ->get(route('educator.sections.index'))
            ->assertOk();

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        $tableRoot = $xpath->query('//*[@id="sections_table_form"]')->item(0);
        $this->assertNotNull($tableRoot, 'sections table root should render');
        $this->assertSame('div', strtolower($tableRoot->nodeName), 'query controls root should not itself be a form');
        $this->assertSame(0, $xpath->query('//*[@id="form_modal"]/ancestor::form')->length, 'shared modal must stay outside any ancestor form');
    }

    public function test_subjects_index_does_not_nest_modal_inside_ancestor_forms(): void
    {
        $this->subject($this->eduA);

        $response = $this->actingAs($this->eduA)
            ->get(route('educator.subjects.index'))
            ->assertOk();

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        $tableRoot = $xpath->query('//*[@id="subjects_table_form"]')->item(0);
        $this->assertNotNull($tableRoot);
        $this->assertSame('div', strtolower($tableRoot->nodeName));
        $this->assertSame(0, $xpath->query('//*[@id="form_modal"]/ancestor::form')->length);
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
            $perm = Permission::create([
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
            // subject_ids[] drives create (one assessment per subject); subject_id drives edit.
            'assessment_code' => 'Q1', 'subject_ids' => [$subject->id], 'subject_id' => $subject->id, 'term' => $this->term->id,
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
