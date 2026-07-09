<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Enrolled;
use App\Models\LearningMaterial;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Task 25 — notification read/delivery side. Headline assertions: every read/count/mark operation
// is owner-scoped (a user can never touch another user's messages), and the two fixed triggers
// (material delete emits; material upload emits ONE message carrying the file count).
class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $educator;

    private User $student;

    private User $otherStudent;

    private Subject $subject;

    private Section $section;

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
        $this->section = Section::create(['educator_id' => $this->educator->id, 'academic_term_id' => $term->id, 'section_name' => 'A1']);
        $this->subject = Subject::create(['educator_id' => $this->educator->id, 'sections_id' => $this->section->id, 'subject_code' => 'M1', 'subject_name' => 'Math']);

        // Only $student is actively enrolled; $otherStudent is not (tests active-enrollment filtering).
        Enrolled::create(['student_id' => $this->student->id, 'educator_id' => $this->educator->id, 'subject_id' => $this->subject->id, 'is_active' => true]);
    }

    // ---- Phase 6: read / unread count, owner-scoped ----

    public function test_index_is_owner_scoped_newest_first_and_capped_at_ten(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->makeNotif($this->student->id, ['title' => "note-{$i}", 'created_at' => now()->addSeconds($i)]);
        }
        $this->makeNotif($this->otherStudent->id, ['title' => 'other-user-note']); // must not leak in

        // Poll endpoint: unread count + a server-rendered items fragment, owner-scoped.
        $res = $this->actingAs($this->student)->getJson(route('notifications.index'))->assertOk();

        $res->assertJsonPath('unread_count', 12);
        $html = $res->json('html');
        $this->assertStringContainsString('note-11', $html);          // newest present
        $this->assertStringNotContainsString('note-0', $html);        // capped at 10 → two oldest dropped
        $this->assertStringNotContainsString('other-user-note', $html); // another user's never leaks
    }

    public function test_mark_read_only_touches_own_message(): void
    {
        $mine = $this->makeNotif($this->student->id);
        $theirs = $this->makeNotif($this->otherStudent->id);

        $this->actingAs($this->student)->postJson(route('notifications.read', $mine))->assertOk();
        $this->assertTrue($mine->fresh()->is_read);
        $this->assertNotNull($mine->fresh()->read_at);

        // Another user's message is invisible → 404, and stays unread.
        $this->actingAs($this->student)->postJson(route('notifications.read', $theirs))->assertNotFound();
        $this->assertFalse($theirs->fresh()->is_read);
    }

    public function test_clicking_a_notification_marks_only_it_read_then_redirects(): void
    {
        $mine = $this->makeNotif($this->student->id, [
            'link_path' => route('student.assessments.index'),
        ]);
        $otherMine = $this->makeNotif($this->student->id);
        $theirs = $this->makeNotif($this->otherStudent->id);

        $this->actingAs($this->student)
            ->get(route('notifications.open', $mine))
            ->assertRedirect('/student/assessments');

        $this->assertTrue($mine->fresh()->is_read);
        $this->assertNotNull($mine->fresh()->read_at);
        $this->assertFalse($otherMine->fresh()->is_read);
        $this->assertFalse($theirs->fresh()->is_read);

        $this->actingAs($this->student)
            ->get(route('notifications.open', $theirs))
            ->assertNotFound();
    }

    public function test_mark_all_read_clears_only_callers_unread(): void
    {
        $this->makeNotif($this->student->id);
        $this->makeNotif($this->student->id);
        $this->makeNotif($this->otherStudent->id);

        $this->actingAs($this->student)->postJson(route('notifications.read-all'))->assertOk()->assertJsonPath('unread_count', 0);

        $this->assertSame(0, Notification::forRecipient($this->student->id)->where('is_read', false)->count());
        $this->assertSame(1, Notification::forRecipient($this->otherStudent->id)->where('is_read', false)->count());
    }

    // ---- Bell UI: All tab renders real data via the view composer ----

    public function test_bell_renders_real_notifications_on_authenticated_page(): void
    {
        $this->makeNotif($this->student->id, ['title' => 'New assessment published']);

        $res = $this->actingAs($this->student)->get(route('student.assessments.index'))->assertOk();

        $res->assertSee('New assessment published');             // real notification title in the drawer
        $res->assertSee($this->educator->name);                  // actor name rendered
        $res->assertSee(route('notifications.read-all'), false); // mark-all form target present (unread > 0)
    }

    public function test_bell_shows_unread_count_indicator(): void
    {
        // No unread → no indicator element rendered (the id="" attribute, not the JS string ref).
        $this->actingAs($this->student)->get(route('student.assessments.index'))
            ->assertOk()->assertDontSee('id="notifications_bell_dot"', false);

        // >9 unread → indicator present, capped label.
        for ($i = 0; $i < 12; $i++) {
            $this->makeNotif($this->student->id);
        }
        $this->actingAs($this->student)->get(route('student.assessments.index'))
            ->assertOk()->assertSee('id="notifications_bell_dot"', false)->assertSee('9+');
    }

    public function test_bell_general_notification_shows_detail_badges(): void
    {
        $this->makeNotif($this->student->id, [
            'event_type' => 'assessment_created', 'title' => 'New assessment published',
            'subject_id' => $this->subject->id, 'section_id' => $this->section->id,
        ]);

        $res = $this->actingAs($this->student)->get(route('student.assessments.index'))->assertOk();

        $res->assertSee($this->subject->subject_code); // M1 badge
        $res->assertSee($this->section->section_name); // A1 badge
    }

    // Regression: link_path stored as an absolute URL (route() default) must render as a host-less
    // path so a notification created under one host (e.g. 127.0.0.1:8000) still works on another.
    public function test_bell_link_renders_host_relative_path(): void
    {
        $notification = $this->makeNotif($this->student->id, [
            'title' => 'Go here', 'link_path' => 'http://127.0.0.1:8000/student/assessments',
        ]);

        $res = $this->actingAs($this->student)->get(route('student.assessments.index'))->assertOk();

        preg_match('/<a[^>]+data-kt-notif-item[^>]*>/', $res->getContent(), $matches);

        $this->assertStringContainsString('href="'.route('notifications.open', $notification, false).'"', $matches[0] ?? '');
        $this->assertStringNotContainsString('http://127.0.0.1:8000/student/assessments', $matches[0] ?? '');
    }

    public function test_bell_material_upload_shows_file_card(): void
    {
        $this->makeNotif($this->student->id, [
            'event_type' => 'learning_material_uploaded', 'title' => '3 new learning materials',
            'subject_id' => $this->subject->id, 'section_id' => $this->section->id,
            'metadata' => ['file_count' => 3],
        ]);

        $res = $this->actingAs($this->student)->get(route('student.assessments.index'))->assertOk();

        $res->assertSee('3 files'); // file-card count, not badges
    }

    // ---- Phase 3: the two fixed material triggers ----

    public function test_material_delete_emits_to_active_enrolled_students_only(): void
    {
        Storage::fake('local');
        $material = LearningMaterial::create([
            'educator_id' => $this->educator->id, 'subject_id' => $this->subject->id, 'section_id' => $this->section->id,
            'storage_bucket' => 'local', 'storage_path' => 'learning-materials/x.pdf', 'file_name' => 'x.pdf',
            'file_extension' => 'pdf', 'mime_type' => 'application/pdf', 'file_size' => 1, 'is_active' => true,
        ]);

        $this->actingAs($this->educator)->delete(route('educator.materials.destroy', $material))->assertRedirect();

        $this->assertDatabaseHas('tbl_notifications', [
            'recipient_user_id' => $this->student->id, 'event_type' => 'learning_material_deleted',
        ]);
        $this->assertDatabaseMissing('tbl_notifications', [
            'recipient_user_id' => $this->otherStudent->id, 'event_type' => 'learning_material_deleted',
        ]);
    }

    public function test_material_upload_emits_one_message_with_file_count(): void
    {
        Storage::fake('local');

        $this->actingAs($this->educator)->post(route('educator.materials.store'), [
            'subject_ids' => [$this->subject->id],
            'files' => [UploadedFile::fake()->create('a.pdf', 10), UploadedFile::fake()->create('b.pdf', 10)],
        ])->assertRedirect();

        $this->assertSame(2, LearningMaterial::where('subject_id', $this->subject->id)->count());

        // Exactly one notification to the enrolled student, carrying the file count (not one per file).
        $notifs = Notification::forRecipient($this->student->id)->where('event_type', 'learning_material_uploaded')->get();
        $this->assertCount(1, $notifs);
        $this->assertSame(2, $notifs->first()->metadata['file_count']);
    }

    private function makeNotif(int $recipientId, array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'recipient_user_id' => $recipientId,
            'actor_user_id' => $this->educator->id,
            'event_type' => 'assessment_created',
            'title' => 'x', 'message' => '', 'is_read' => false,
        ], $overrides));
    }

    private function makeUser(string $type, string $roleName): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $roleName)->value('id'));

        return $user;
    }
}
