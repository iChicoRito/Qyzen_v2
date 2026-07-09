<?php

namespace Tests\Feature\Student;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\GroupChat;
use App\Models\GroupChatMessage;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Score;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Stage H: student feature tests. The headline assertion is the H6 INVARIANT — correct_answer
// never reaches the client — plus server-side grading (>=75%), enrollment gating, and the
// review-display gate.
class StudentFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $educator;

    private User $student;

    private User $otherStudent;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->educator = $this->makeUser('educator', 'educator');
        $this->student = $this->makeUser('student', 'student');
        $this->otherStudent = $this->makeUser('student', 'student');

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);
        $section = Section::create(['educator_id' => $this->educator->id, 'academic_term_id' => $term->id, 'section_name' => 'A1']);
        $subject = Subject::create(['educator_id' => $this->educator->id, 'sections_id' => $section->id, 'subject_code' => 'M1', 'subject_name' => 'Math']);

        // open window now.
        $this->assessment = Assessment::create([
            'educator_id' => $this->educator->id, 'subject_id' => $subject->id, 'section_id' => $section->id,
            'assessment_code' => 'Q1', 'time_limit' => '30', 'term' => $term->id, 'is_active' => true,
            'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
            'start_time' => '00:00', 'end_time' => '23:59', 'allow_review' => false,
        ]);

        // 4 questions: 3 MC + 1 identification. All 4 are eligible and pool_size=4, so every
        // attempt draws exactly this fixed set — the pre-pool test assumptions still hold.
        $quizIds = [];
        foreach ([['2+2', 'multiple_choice', ['A' => '3', 'B' => '4'], 'B'],
            ['3+3', 'multiple_choice', ['A' => '6', 'B' => '5'], 'A'],
            ['1+1', 'multiple_choice', ['A' => '2', 'B' => '3'], 'A'],
            ['Capital of France', 'identification', null, 'Paris']] as [$q, $type, $choices, $correct]) {
            $quiz = Quiz::create([
                'subject_id' => $subject->id, 'educator_id' => $this->educator->id,
                'question' => $q, 'quiz_type' => $type, 'choices' => $choices, 'correct_answer' => $correct,
            ]);
            $quizIds[] = $quiz->id;
        }
        $this->assessment->eligibleQuizzes()->sync($quizIds);
        $this->assessment->update(['pool_size' => count($quizIds)]);

        Enrolled::create(['student_id' => $this->student->id, 'educator_id' => $this->educator->id, 'subject_id' => $subject->id, 'is_active' => true]);
    }

    // ---- gate ----

    public function test_non_student_bounced_from_student_routes(): void
    {
        $this->actingAs($this->educator)->get(route('student.dashboard'))->assertStatus(302);
    }

    public function test_student_sees_only_enrolled_assessments(): void
    {
        $this->actingAs($this->student)->get(route('student.assessments.index'))->assertOk()->assertSee('Q1');
        // non-enrolled student can't view details (policy denies)
        $this->actingAs($this->otherStudent)->get(route('student.assessments.details', $this->assessment))->assertForbidden();
    }

    // ---- H6 INVARIANT: correct_answer never reaches the client ----

    public function test_take_quiz_page_never_contains_correct_answer(): void
    {
        $response = $this->actingAs($this->student)->get(route('student.take-quiz', $this->assessment));
        $response->assertOk();

        $html = $response->getContent();
        // correct keys / identification answer must not appear as answer data.
        $this->assertStringNotContainsString('correct_answer', $html);
        $this->assertStringNotContainsString('Paris', $html, 'identification answer leaked to take-quiz page');
    }

    public function test_quiz_model_hides_correct_answer_in_json(): void
    {
        $quiz = $this->assessment->eligibleQuizzes()->first();
        $this->assertArrayNotHasKey('correct_answer', $quiz->toArray());
    }

    // ---- H6: server-side grading >= 75% ----

    public function test_submit_grades_server_side_and_passes_at_75_percent(): void
    {
        $quizzes = $this->assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->get();
        // answer 3 of 4 correctly = 75% → pass.
        $answers = [
            $quizzes[0]->id => 'B', // correct
            $quizzes[1]->id => 'A', // correct
            $quizzes[2]->id => 'A', // correct
            $quizzes[3]->id => 'Wrong', // wrong
        ];

        $this->actingAs($this->student)
            ->post(route('student.take-quiz.submit', $this->assessment), ['answers' => $answers, 'warnings' => 0])
            ->assertRedirect();

        $score = Score::where('student_id', $this->student->id)->where('assessment_id', $this->assessment->id)->first();
        $this->assertSame(3, $score->score);
        $this->assertSame(4, $score->total_questions);
        $this->assertTrue($score->is_passed); // 3/4 = 75%
        $this->assertSame('passed', $score->status);
    }

    public function test_submit_below_75_percent_fails(): void
    {
        $quizzes = $this->assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->get();
        $answers = [$quizzes[0]->id => 'B', $quizzes[1]->id => 'B', $quizzes[2]->id => 'B', $quizzes[3]->id => 'x']; // 1/4

        $this->actingAs($this->student)
            ->post(route('student.take-quiz.submit', $this->assessment), ['answers' => $answers])
            ->assertRedirect();

        $score = Score::where('student_id', $this->student->id)->first();
        $this->assertFalse($score->is_passed);
        $this->assertSame('failed', $score->status);
    }

    public function test_submit_notifies_the_educator(): void
    {
        $quizzes = $this->assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->get();
        $this->actingAs($this->student)->post(route('student.take-quiz.submit', $this->assessment), [
            'answers' => [$quizzes[0]->id => 'B'],
        ])->assertRedirect();

        $this->assertDatabaseHas('tbl_notifications', [
            'recipient_user_id' => $this->educator->id, 'event_type' => 'quiz_submitted',
        ]);
    }

    // ---- H7: review-display gate ----

    public function test_result_hides_correct_answer_when_review_disabled_and_wrong(): void
    {
        $quizzes = $this->assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->get();
        // get the identification one wrong; allow_review is false.
        $this->actingAs($this->student)->post(route('student.take-quiz.submit', $this->assessment), [
            'answers' => [$quizzes[0]->id => 'B', $quizzes[3]->id => 'London'],
        ])->assertRedirect();

        $score = Score::where('student_id', $this->student->id)->first();
        $html = $this->actingAs($this->student)->get(route('student.scores.show', $score))->assertOk()->getContent();

        // correct answer for the wrong, review-disabled question must be hidden.
        $this->assertStringNotContainsString('Paris', $html);
    }

    // Task 01: a disabled review must hide the whole per-question block, not just leak the
    // correct_answer value on questions the student got right.
    public function test_result_hides_entire_review_block_when_review_disabled(): void
    {
        $quizzes = $this->assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->get();
        // answer the first question CORRECTLY — the old gate exposed correct_answer here.
        $this->actingAs($this->student)->post(route('student.take-quiz.submit', $this->assessment), [
            'answers' => [$quizzes[0]->id => 'B'],
        ])->assertRedirect();

        $score = Score::where('student_id', $this->student->id)->first();
        $response = $this->actingAs($this->student)->get(route('student.scores.show', $score))->assertOk();

        $response->assertSee('Review is not enabled for this assessment.');
        $response->assertDontSee('2+2'); // question text must not render per-question at all
    }

    public function test_result_shows_review_block_when_review_enabled(): void
    {
        $this->assessment->update(['allow_review' => true]);
        $quizzes = $this->assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->get();
        $this->actingAs($this->student)->post(route('student.take-quiz.submit', $this->assessment), [
            'answers' => [$quizzes[0]->id => 'B'],
        ])->assertRedirect();

        $score = Score::where('student_id', $this->student->id)->first();
        $response = $this->actingAs($this->student)->get(route('student.scores.show', $score))->assertOk();

        $response->assertDontSee('Review is not enabled for this assessment.');
        $response->assertSee('2+2');
    }

    // Task 01: hints — bounded reveal, only when the educator turned them on.
    public function test_hint_reveals_answer_when_enabled_and_respects_hint_count(): void
    {
        $this->assessment->update(['allow_hint' => true, 'hint_count' => 1]);
        $this->actingAs($this->student)->get(route('student.take-quiz', $this->assessment))->assertOk();
        $quiz = $this->assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->first();

        $this->actingAs($this->student)
            ->postJson(route('student.take-quiz.hint', $this->assessment), ['quiz_id' => $quiz->id])
            ->assertOk()
            ->assertJson(['remaining' => 0]);

        // hint_count is 1 and already used — a second reveal must be rejected.
        $otherQuiz = $this->assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->skip(1)->first();
        $this->actingAs($this->student)
            ->postJson(route('student.take-quiz.hint', $this->assessment), ['quiz_id' => $otherQuiz->id])
            ->assertStatus(422);
    }

    public function test_hint_endpoint_rejects_when_assessment_has_hints_disabled(): void
    {
        $this->actingAs($this->student)->get(route('student.take-quiz', $this->assessment))->assertOk();
        $quiz = $this->assessment->eligibleQuizzes()->first();

        $this->actingAs($this->student)
            ->postJson(route('student.take-quiz.hint', $this->assessment), ['quiz_id' => $quiz->id])
            ->assertStatus(422);
    }

    // Task 01: a deactivated assessment must be rejected server-side even if a student's
    // already-loaded page still shows "Take Assessment" (stale client-side status).
    public function test_take_quiz_rejects_deactivated_assessment_server_side(): void
    {
        $this->assessment->update(['is_active' => false]);

        $this->actingAs($this->student)
            ->get(route('student.take-quiz', $this->assessment))
            ->assertRedirect(route('student.assessments.index'));
    }

    public function test_student_cannot_view_others_score(): void
    {
        $quizzes = $this->assessment->eligibleQuizzes()->orderBy('tbl_quizzes.id')->get();
        $this->actingAs($this->student)->post(route('student.take-quiz.submit', $this->assessment), [
            'answers' => [$quizzes[0]->id => 'B'],
        ]);
        $score = Score::where('student_id', $this->student->id)->first();

        // 404 (not 403): non-owned attempts resolve to "Result not found" without leaking existence.
        $this->actingAs($this->otherStudent)->get(route('student.scores.show', $score))->assertNotFound();
    }

    public function test_student_materials_are_searched_filtered_and_scoped_on_the_server(): void
    {
        $subject = Subject::findOrFail($this->assessment->subject_id);
        $section = Section::findOrFail($this->assessment->section_id);

        LearningMaterial::create([
            'educator_id' => $this->educator->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'storage_bucket' => 'local',
            'storage_path' => 'learning-materials/algebra.pdf',
            'file_name' => 'Algebra Notes.pdf',
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'is_active' => true,
        ]);
        LearningMaterial::create([
            'educator_id' => $this->educator->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'storage_bucket' => 'local',
            'storage_path' => 'learning-materials/biology.pdf',
            'file_name' => 'Biology Notes.pdf',
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'is_active' => true,
        ]);

        $otherSubject = Subject::create([
            'educator_id' => $this->educator->id,
            'sections_id' => $section->id,
            'subject_code' => 'SCI',
            'subject_name' => 'Science',
        ]);
        LearningMaterial::create([
            'educator_id' => $this->educator->id,
            'subject_id' => $otherSubject->id,
            'section_id' => $section->id,
            'storage_bucket' => 'local',
            'storage_path' => 'learning-materials/hidden.pdf',
            'file_name' => 'Hidden Notes.pdf',
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'is_active' => true,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.materials.index', [
                'search' => 'Algebra',
                'subject' => $subject->id,
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertSee('Algebra Notes.pdf')
            ->assertDontSee('Biology Notes.pdf')
            ->assertDontSee('Hidden Notes.pdf');
    }

    public function test_student_can_send_group_chat_message(): void
    {
        $chat = GroupChat::create([
            'educator_id' => $this->educator->id,
            'subject_id' => $this->assessment->subject_id,
            'section_id' => $this->assessment->section_id,
        ]);

        $this->actingAs($this->student)
            ->from(route('student.chats.show', $chat))
            ->post(route('student.chats.messages.send', $chat), ['content' => 'Hello class!'])
            ->assertRedirect(route('student.chats.show', $chat));

        $this->assertDatabaseHas('tbl_group_chat_messages', [
            'group_chat_id' => $chat->id,
            'sender_user_id' => $this->student->id,
            'content' => 'Hello class!',
        ]);
    }

    public function test_scores_index_keeps_modal_outside_ancestor_forms(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.scores.index'))
            ->assertOk();

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        $tableRoot = $xpath->query('//*[@id="student_scores_table_form"]')->item(0);
        $this->assertNotNull($tableRoot);
        $this->assertSame('div', strtolower($tableRoot->nodeName));
        $this->assertSame(0, $xpath->query('//*[@id="form_modal"]/ancestor::form')->length);
    }

    // ---- helper ----

    private function makeUser(string $type, string $roleName): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $roleName)->value('id'));

        return $user;
    }
}
