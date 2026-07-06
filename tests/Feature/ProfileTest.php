<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_with_tabs_and_modals(): void
    {
        $user = User::factory()->educator()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Account Settings')
            ->assertSee('Personal Information')
            ->assertSee('Crop Profile Picture')
            ->assertSee('Change email address')
            ->assertSee($user->user_id);
    }

    public function test_student_sees_name_locked_note(): void
    {
        $user = User::factory()->create(['user_type' => 'student', 'email_verified_at' => now()]);
        // no roles attached -> hasRole('student') is false; attach the student role so the lock applies.
        Role::create(['name' => 'student', 'description' => 'student', 'is_active' => true]);
        $user->roles()->attach(Role::where('name', 'student')->value('id'));

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Students can update email and media only.');
    }

    public function test_profile_update_removes_media_when_flagged(): void
    {
        Storage::fake('public');
        $user = User::factory()->educator()->create([
            'email_verified_at' => now(),
            'profile_picture' => 'profile-media/1/pic.png',
            'cover_photo' => 'profile-media/1/cover.png',
        ]);

        $this->actingAs($user)->put(route('profile.update'), [
            'given_name' => $user->given_name,
            'surname' => $user->surname,
            'remove_profile_picture' => '1',
            'remove_cover_photo' => '1',
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNull($user->profile_picture);
        $this->assertNull($user->cover_photo);
    }

    public function test_password_updates_without_current_password(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'password' => 'OldPass1!']);

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Password updated successfully.');

        $this->assertTrue(Hash::check('NewPass2!', $user->fresh()->password));
    }

    public function test_password_update_rejects_mismatched_confirmation(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'password' => 'OldPass1!']);

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'password' => 'NewPass2!',
                'password_confirmation' => 'Different2!',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldPass1!', $user->fresh()->password));
    }

    // Email is switched only via the Google flow now — the profile form must ignore any email input.
    public function test_profile_update_does_not_change_email(): void
    {
        $user = User::factory()->create([
            'email' => 'keep@example.com',
            'email_verified_at' => now(),
            'given_name' => 'Old',
            'surname' => 'Name',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'given_name' => 'New',
                'surname' => 'Person',
                'email' => 'hacker@example.com',
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('keep@example.com', $user->email);
        $this->assertSame('New', $user->given_name);
    }
}
