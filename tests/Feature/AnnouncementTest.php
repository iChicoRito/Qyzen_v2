<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Enrolled;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private User $educator;

    private User $otherEducator;

    private User $student;

    private User $otherStudent;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->educator = $this->makeUser('educator');
        $this->otherEducator = $this->makeUser('educator');
        $this->student = $this->makeUser('student');
        $this->otherStudent = $this->makeUser('student');

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $term = AcademicTerm::create([
            'term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id,
        ]);
        $section = Section::create([
            'educator_id' => $this->educator->id, 'academic_term_id' => $term->id,
            'section_name' => 'A1', 'is_active' => true,
        ]);
        $this->subject = Subject::create([
            'educator_id' => $this->educator->id, 'sections_id' => $section->id,
            'subject_code' => 'M1', 'subject_name' => 'Math', 'is_active' => true,
        ]);

        Enrolled::create([
            'student_id' => $this->student->id, 'educator_id' => $this->educator->id,
            'subject_id' => $this->subject->id, 'is_active' => true,
        ]);
        Enrolled::create([
            'student_id' => $this->otherStudent->id, 'educator_id' => $this->educator->id,
            'subject_id' => $this->subject->id, 'is_active' => false,
        ]);
    }

    public function test_educator_can_create_edit_and_delete_announcement(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->educator)->post(route('educator.announcements.store'), [
            'title' => 'Exam update',
            'description' => 'Read this first.',
            'body' => '<p>Hello <script>alert(1)</script><strong>class</strong></p>',
            'subject_id' => $this->subject->id,
            'is_global' => '0',
            'images' => [UploadedFile::fake()->create('notice.jpg', 10, 'image/jpeg')],
        ]);

        $response->assertRedirect(route('educator.announcements.index'));
        $announcement = Announcement::firstOrFail();
        $this->assertSame('<p>Hello <strong>class</strong></p>', $announcement->body);
        $this->assertCount(1, $announcement->images);
        Storage::disk('local')->assertExists($announcement->images[0]['path']);

        $this->actingAs($this->educator)->put(route('educator.announcements.update', $announcement), [
            'title' => 'Updated exam', 'description' => '', 'body' => '<p>Updated</p>',
            'subject_id' => $this->subject->id, 'is_global' => '0',
        ])->assertRedirect(route('educator.announcements.index'));
        $this->assertSame('Updated exam', $announcement->fresh()->title);

        $path = $announcement->fresh()->images[0]['path'];
        $this->actingAs($this->educator)
            ->delete(route('educator.announcements.destroy', $announcement))
            ->assertRedirect(route('educator.announcements.index'));
        $this->assertDatabaseMissing('tbl_announcements', ['id' => $announcement->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_announcement_editor_binding_is_delegated_for_modal_fragments(): void
    {
        $response = $this->actingAs($this->educator)->get(route('educator.announcements.index'))->assertOk();

        $response->assertSee("document.addEventListener('input'", false)
            ->assertSee("closest('[data-editor]')", false)
            ->assertSee("closest('[data-editor-command]')", false);
    }

    public function test_announcement_targeting_notifications_and_student_cards_are_scoped(): void
    {
        $subjectAnnouncement = $this->createAnnouncement(['subject_id' => $this->subject->id]);
        $globalAnnouncement = $this->createAnnouncement(['subject_id' => null, 'is_global' => true, 'title' => 'Global notice']);

        $this->assertDatabaseHas('tbl_notifications', [
            'recipient_user_id' => $this->student->id,
            'event_type' => 'announcement_created',
        ]);
        $this->assertSame(2, Notification::where('recipient_user_id', $this->student->id)->where('event_type', 'announcement_created')->count());
        $this->assertSame(0, Notification::where('recipient_user_id', $this->otherStudent->id)->where('event_type', 'announcement_created')->count());

        $response = $this->actingAs($this->student)->get(route('student.announcements.index'));
        $response->assertOk()->assertSee($subjectAnnouncement->title)->assertSee($globalAnnouncement->title);
        $response->assertSee('New announcement: '.$subjectAnnouncement->title);
        preg_match_all('/<article\b.*?<\/article>/s', $response->getContent(), $cards);
        $cardHtml = implode('', $cards[0]);
        $this->assertStringNotContainsString('ki-dots-vertical', $cardHtml);
        $this->assertStringNotContainsString('ki-heart', $cardHtml);
        $this->assertStringNotContainsString('ki-message-text', $cardHtml);

        $this->actingAs($this->otherStudent)->get(route('student.announcements.index'))
            ->assertOk()->assertDontSee($subjectAnnouncement->title)->assertDontSee($globalAnnouncement->title);
    }

    public function test_student_announcements_use_timeline_sidebar_and_full_width_feed(): void
    {
        $announcement = $this->createAnnouncement(['title' => 'Timeline announcement']);

        $response = $this->actingAs($this->student)->get(route('student.announcements.index'))->assertOk();
        $response->assertSee('Announcements timeline')
            ->assertSee('lg:grid-cols-[280px_minmax(0,1fr)]', false)
            ->assertSee($announcement->title)
            ->assertDontSee('View All');

        preg_match('/id="announcement_timeline".*?<\/div>\s*<\/div>/s', $response->getContent(), $timeline);
        $this->assertNotEmpty($timeline);
        $this->assertStringNotContainsString('ki-heart', $timeline[0]);
    }

    public function test_educator_ownership_and_upload_validation_are_enforced(): void
    {
        $announcement = Announcement::create([
            'educator_id' => $this->educator->id, 'subject_id' => $this->subject->id,
            'title' => 'Owned', 'body' => '<p>Body</p>', 'is_global' => false, 'is_active' => true,
        ]);

        $this->actingAs($this->otherEducator)->get(route('educator.announcements.edit', $announcement))->assertForbidden();
        $this->actingAs($this->otherEducator)->delete(route('educator.announcements.destroy', $announcement))->assertForbidden();

        $this->actingAs($this->educator)->post(route('educator.announcements.store'), [
            'title' => 'Bad', 'body' => '<p>Body</p>', 'subject_id' => $this->subject->id,
            'is_global' => '0', 'images' => [UploadedFile::fake()->create('bad.pdf', 10, 'application/pdf')],
        ])->assertSessionHasErrors('images.0');

        $this->actingAs($this->educator)->post(route('educator.announcements.store'), [
            'title' => 'Too large', 'body' => '<p>Body</p>', 'subject_id' => $this->subject->id,
            'is_global' => '0', 'images' => [UploadedFile::fake()->create('large.jpg', 10241, 'image/jpeg')],
        ])->assertSessionHasErrors('images.0');
    }

    public function test_announcement_images_are_private_and_enrollment_gated(): void
    {
        Storage::fake('local');
        $this->actingAs($this->educator)->post(route('educator.announcements.store'), [
            'title' => 'Image notice', 'body' => '<p>Body</p>', 'subject_id' => $this->subject->id,
            'is_global' => '0', 'images' => [UploadedFile::fake()->create('notice.png', 10, 'image/png')],
        ])->assertRedirect();
        $announcement = Announcement::latest('id')->firstOrFail();

        $this->actingAs($this->student)->get(route('student.announcements.image', [$announcement, 0]))->assertOk();
        $this->actingAs($this->otherStudent)->get(route('student.announcements.image', [$announcement, 0]))->assertForbidden();
    }

    private function createAnnouncement(array $overrides = []): Announcement
    {
        $this->actingAs($this->educator)->post(route('educator.announcements.store'), array_merge([
            'title' => 'Class announcement', 'body' => '<p>Announcement body</p>',
            'subject_id' => $this->subject->id, 'is_global' => '0',
        ], $overrides))->assertRedirect();

        return Announcement::latest('id')->firstOrFail();
    }

    private function makeUser(string $type): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $type)->value('id'));

        return $user;
    }
}
