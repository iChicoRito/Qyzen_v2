<?php

namespace Tests\Feature;

use App\Events\ConversationActivity;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Enrolled;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

// Task 30 — private 1:1 student/educator messaging. Headline assertions: enrollment (subject-
// agnostic) is the access boundary for starting AND continuing a conversation, edit/delete leave
// markers rather than removing rows, and unread counts track the OTHER participant's read marker.
class PrivateMessagingTest extends TestCase
{
    use RefreshDatabase;

    private User $educator;

    private User $enrolledStudent;

    private User $unenrolledStudent;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->educator = $this->makeUser('educator', 'educator');
        $this->enrolledStudent = $this->makeUser('student', 'student');
        $this->unenrolledStudent = $this->makeUser('student', 'student');

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);
        $section = Section::create(['educator_id' => $this->educator->id, 'academic_term_id' => $term->id, 'section_name' => 'A1']);
        $subject = Subject::create(['educator_id' => $this->educator->id, 'sections_id' => $section->id, 'subject_code' => 'M1', 'subject_name' => 'Math']);

        Enrolled::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id, 'subject_id' => $subject->id, 'is_active' => true]);
    }

    public function test_enrolled_pair_can_start_and_exchange_messages(): void
    {
        $start = $this->actingAs($this->enrolledStudent)
            ->postJson(route('messaging.conversations.store'), ['other_user_id' => $this->educator->id])
            ->assertOk();

        $conversationId = $start->json('conversation_id');
        $this->assertDatabaseHas('tbl_conversations', [
            'id' => $conversationId, 'student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id,
        ]);

        $conversation = Conversation::find($conversationId);

        $sendRes = $this->actingAs($this->enrolledStudent)
            ->postJson(route('messaging.messages.send', $conversation), ['content' => 'Hello!'])
            ->assertOk();
        $this->assertStringContainsString('Hello!', $sendRes->json('html'));

        $this->assertDatabaseHas('tbl_conversation_messages', [
            'conversation_id' => $conversation->id, 'sender_user_id' => $this->enrolledStudent->id, 'content' => 'Hello!',
        ]);

        $reply = $this->actingAs($this->educator)
            ->postJson(route('messaging.messages.send', $conversation), ['content' => 'Hi there'])
            ->assertOk();

        $this->assertStringContainsString('Hi there', $reply->json('html'));
    }

    public function test_contacts_lists_only_active_enrollment_counterparties(): void
    {
        // Student sees the educator they're enrolled with, and not an unrelated educator.
        $otherEducator = $this->makeUser('educator', 'educator');

        // Names are Blade-escaped in the fragment, so compare against e() — faker occasionally rolls a
        // name with an apostrophe (O'Connell) which renders as &#039; and would fail a raw compare.
        $res = $this->actingAs($this->enrolledStudent)->getJson(route('messaging.contacts'))->assertOk();
        $html = $res->json('html');
        $this->assertStringContainsString(e($this->educator->name), $html);
        $this->assertStringNotContainsString(e($otherEducator->name), $html);

        // Educator sees the enrolled student, not the un-enrolled one.
        $eduRes = $this->actingAs($this->educator)->getJson(route('messaging.contacts'))->assertOk();
        $eduHtml = $eduRes->json('html');
        $this->assertStringContainsString(e($this->enrolledStudent->name), $eduHtml);
        $this->assertStringNotContainsString(e($this->unenrolledStudent->name), $eduHtml);

        // Each row shows the person's number + role badge; educator gets the subject/section filter.
        $this->assertStringContainsString($this->enrolledStudent->user_id, $eduHtml); // student number
        $this->assertStringContainsString('Student', $eduHtml);                        // role badge
        $this->assertStringContainsString('chat_drawer_subject_filter', $eduHtml);     // filter dropdown

        // Students don't get the subject filter.
        $this->assertStringNotContainsString('chat_drawer_subject_filter', $html);
    }

    public function test_non_enrolled_pair_is_blocked_at_start_and_send(): void
    {
        $this->actingAs($this->unenrolledStudent)
            ->postJson(route('messaging.conversations.store'), ['other_user_id' => $this->educator->id])
            ->assertForbidden();

        // Even if a conversation row somehow exists (e.g. enrollment later revoked), sending is re-checked.
        $conversation = Conversation::create(['student_id' => $this->unenrolledStudent->id, 'educator_id' => $this->educator->id]);

        $this->actingAs($this->unenrolledStudent)
            ->postJson(route('messaging.messages.send', $conversation), ['content' => 'Hello?'])
            ->assertForbidden();
    }

    public function test_revoking_enrollment_blocks_further_sends_on_an_existing_conversation(): void
    {
        $conversation = Conversation::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id]);

        Enrolled::where('student_id', $this->enrolledStudent->id)->update(['is_active' => false]);

        $this->actingAs($this->enrolledStudent)
            ->postJson(route('messaging.messages.send', $conversation), ['content' => 'Still here?'])
            ->assertForbidden();
    }

    public function test_edit_and_delete_leave_markers_not_row_removal(): void
    {
        $conversation = Conversation::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id]);
        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id, 'sender_user_id' => $this->enrolledStudent->id, 'content' => 'Original',
        ]);

        $editRes = $this->actingAs($this->enrolledStudent)
            ->putJson(route('messaging.messages.update', $message), ['content' => 'Edited'])
            ->assertOk();
        $this->assertStringContainsString('Edited', $editRes->json('html'));
        $this->assertStringContainsString('(edited)', $editRes->json('html'));
        $this->assertNotNull($message->fresh()->edited_at);

        $delRes = $this->actingAs($this->enrolledStudent)
            ->deleteJson(route('messaging.messages.destroy', $message))
            ->assertOk();
        $this->assertStringContainsString('This message was deleted', $delRes->json('html'));

        $fresh = $message->fresh();
        $this->assertNotNull($fresh); // row still exists
        $this->assertSame('', $fresh->content);
        $this->assertNotNull($fresh->message_deleted_at);

        // Another user's message cannot be edited/deleted.
        $othersMessage = ConversationMessage::create([
            'conversation_id' => $conversation->id, 'sender_user_id' => $this->educator->id, 'content' => 'Not yours',
        ]);
        $this->actingAs($this->enrolledStudent)
            ->putJson(route('messaging.messages.update', $othersMessage), ['content' => 'Hijacked'])
            ->assertForbidden();
    }

    public function test_unread_count_drops_after_mark_read(): void
    {
        $conversation = Conversation::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id]);
        ConversationMessage::create(['conversation_id' => $conversation->id, 'sender_user_id' => $this->educator->id, 'content' => 'Hi']);

        $before = $this->actingAs($this->enrolledStudent)->getJson(route('messaging.conversations'))->assertOk();
        $this->assertSame(1, $before->json('unread_count'));

        $this->actingAs($this->enrolledStudent)->getJson(route('messaging.conversations.show', $conversation))->assertOk();

        $after = $this->actingAs($this->enrolledStudent)->getJson(route('messaging.conversations'))->assertOk();
        $this->assertSame(0, $after->json('unread_count'));
    }

    public function test_educator_thread_header_shows_only_active_shared_subject_sections(): void
    {
        $term = AcademicTerm::firstOrFail();
        $section = Section::create(['educator_id' => $this->educator->id, 'academic_term_id' => $term->id, 'section_name' => 'B2']);
        $active = Subject::create(['educator_id' => $this->educator->id, 'sections_id' => $section->id, 'subject_code' => 'ENG1', 'subject_name' => 'English']);
        $inactive = Subject::create(['educator_id' => $this->educator->id, 'sections_id' => $section->id, 'subject_code' => 'HIS1', 'subject_name' => 'History']);
        Enrolled::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id, 'subject_id' => $active->id, 'is_active' => true]);
        Enrolled::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id, 'subject_id' => $inactive->id, 'is_active' => false]);
        $conversation = Conversation::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id]);

        $res = $this->actingAs($this->educator)
            ->getJson(route('messaging.conversations.show', $conversation).'?peek=1')
            ->assertOk();

        $this->assertStringContainsString('ENG1', $res->json('context_html'));
        $this->assertStringContainsString('B2', $res->json('context_html'));
        $this->assertStringNotContainsString('HIS1', $res->json('context_html'));
    }

    public function test_peek_poll_reads_the_thread_without_marking_it_read(): void
    {
        $conversation = Conversation::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id]);
        ConversationMessage::create(['conversation_id' => $conversation->id, 'sender_user_id' => $this->educator->id, 'content' => 'Hi']);

        // The read-only polling path (?peek=1) returns the thread + a change signature but writes nothing.
        $this->actingAs($this->enrolledStudent)
            ->getJson(route('messaging.conversations.show', $conversation).'?peek=1')
            ->assertOk()
            ->assertJsonStructure(['signature', 'html']);
        $this->assertDatabaseMissing('tbl_conversation_reads', [
            'conversation_id' => $conversation->id, 'user_id' => $this->enrolledStudent->id,
        ]);
        $this->assertSame(1, $this->actingAs($this->enrolledStudent)->getJson(route('messaging.conversations'))->json('unread_count'));

        // A real open (no peek) still marks it read.
        $this->actingAs($this->enrolledStudent)->getJson(route('messaging.conversations.show', $conversation))->assertOk();
        $this->assertSame(0, $this->actingAs($this->enrolledStudent)->getJson(route('messaging.conversations'))->json('unread_count'));
    }

    public function test_unread_message_lights_up_both_the_message_and_notification_icons(): void
    {
        $conversation = Conversation::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id]);
        ConversationMessage::create(['conversation_id' => $conversation->id, 'sender_user_id' => $this->educator->id, 'content' => 'Hello there']);

        $res = $this->actingAs($this->enrolledStudent)->get(route('student.assessments.index'))->assertOk();

        // Message icon badge present, and the bell badge (combined total) present despite 0 notifications.
        $res->assertSee('id="chat_bell_dot"', false);
        $res->assertSee('id="notifications_bell_dot"', false);
    }

    public function test_no_badges_when_nothing_unread(): void
    {
        // enrolledStudent has no messages and no notifications → neither badge renders.
        $res = $this->actingAs($this->enrolledStudent)->get(route('student.assessments.index'))->assertOk();

        $res->assertDontSee('id="chat_bell_dot"', false);
        $res->assertDontSee('id="notifications_bell_dot"', false);
    }

    public function test_foreign_conversation_and_message_ids_are_forbidden(): void
    {
        $otherEducator = $this->makeUser('educator', 'educator');
        $foreignConversation = Conversation::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $otherEducator->id]);
        $foreignMessage = ConversationMessage::create([
            'conversation_id' => $foreignConversation->id, 'sender_user_id' => $otherEducator->id, 'content' => 'x',
        ]);

        $this->actingAs($this->educator)->getJson(route('messaging.conversations.show', $foreignConversation))->assertForbidden();
        $this->actingAs($this->educator)->deleteJson(route('messaging.messages.destroy', $foreignMessage))->assertForbidden();
    }

    public function test_sending_a_message_broadcasts_to_the_other_participant_only(): void
    {
        Event::fake([ConversationActivity::class]);

        $conversation = Conversation::create(['student_id' => $this->enrolledStudent->id, 'educator_id' => $this->educator->id]);

        $this->actingAs($this->enrolledStudent)
            ->postJson(route('messaging.messages.send', $conversation), ['content' => 'Ping'])
            ->assertOk();

        // Recipient is the educator (the OTHER party), not the sending student.
        Event::assertDispatched(ConversationActivity::class, fn (ConversationActivity $e) => $e->recipientId === $this->educator->id
            && $e->conversationId === $conversation->id);
    }

    private function makeUser(string $type, string $roleName): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $roleName)->value('id'));

        return $user;
    }
}
