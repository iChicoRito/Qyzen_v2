<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileMediaPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_media_uses_public_profile_media_directory_and_app_url(): void
    {
        Config::set('filesystems.disks.profile_media.url', 'https://qyzen.test/profile-media');
        Storage::fake('profile_media');
        $user = User::factory()->educator()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->put(route('profile.update'), [
            'given_name' => $user->given_name,
            'surname' => $user->surname,
            'profile_picture' => UploadedFile::fake()->create('avatar.png', 10, 'image/png'),
            'cover_photo' => UploadedFile::fake()->create('cover.jpg', 10, 'image/jpeg'),
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();
        Storage::disk('profile_media')->assertExists($user->profile_picture);
        Storage::disk('profile_media')->assertExists($user->cover_photo);
        $this->assertSame(public_path('profile-media'), config('filesystems.disks.profile_media.root'));
        $this->assertSame('https://qyzen.test/profile-media', config('filesystems.disks.profile_media.url'));

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(Storage::disk('profile_media')->url($user->profile_picture), false)
            ->assertSee(Storage::disk('profile_media')->url($user->cover_photo), false);

        $notification = Notification::create([
            'recipient_user_id' => $user->id,
            'actor_user_id' => $user->id,
            'event_type' => 'assessment_created',
            'title' => 'Assessment created',
            'message' => 'A new assessment is available.',
            'link_path' => '/notifications',
        ]);

        $html = view('layouts.partials._notification_items', [
            'notifications' => collect([$notification->load('actor')]),
        ])->render();

        $this->assertStringContainsString(Storage::disk('profile_media')->url($user->profile_picture), $html);
    }

    public function test_replacing_and_removing_profile_media_delete_persistent_files(): void
    {
        Storage::fake('profile_media');
        $user = User::factory()->educator()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->put(route('profile.update'), [
            'given_name' => $user->given_name,
            'surname' => $user->surname,
            'profile_picture' => UploadedFile::fake()->create('old.png', 10, 'image/png'),
        ]);
        $oldPath = $user->fresh()->profile_picture;

        $this->actingAs($user)->put(route('profile.update'), [
            'given_name' => $user->given_name,
            'surname' => $user->surname,
            'profile_picture' => UploadedFile::fake()->create('new.png', 10, 'image/png'),
        ]);
        $newPath = $user->fresh()->profile_picture;

        Storage::disk('profile_media')->assertMissing($oldPath);
        Storage::disk('profile_media')->assertExists($newPath);

        $this->actingAs($user)->put(route('profile.update'), [
            'given_name' => $user->given_name,
            'surname' => $user->surname,
            'remove_profile_picture' => '1',
        ]);

        Storage::disk('profile_media')->assertMissing($newPath);
        $this->assertNull($user->fresh()->profile_picture);
    }
}
