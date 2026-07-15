<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }
    }

    public function test_flagged_user_is_redirected_to_forced_password_change(): void
    {
        $user = $this->makeUser(mustChangePassword: true);

        $this->actingAs($user)
            ->get(route('dashboard.redirect'))
            ->assertRedirect(route('password.force.edit'));

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('password.force.edit'));
    }

    public function test_flagged_user_can_refresh_forced_change_page_and_logout(): void
    {
        $user = $this->makeUser(mustChangePassword: true);

        $this->actingAs($user)
            ->get(route('password.force.edit'))
            ->assertOk()
            ->assertSee('Change your temporary password');

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_relogin_keeps_flagged_user_locked_until_password_changes(): void
    {
        $user = $this->makeUser(mustChangePassword: true);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'TempPass1!',
        ])->assertRedirect('/dashboard');

        $this->get(route('student.dashboard'))
            ->assertRedirect(route('password.force.edit'));
    }

    public function test_invalid_forced_password_change_keeps_user_flagged(): void
    {
        $user = $this->makeUser(mustChangePassword: true);

        $this->actingAs($user)
            ->put(route('password.force.update'), [
                'current_password' => 'wrong-password',
                'password' => 'TempPass1!',
                'password_confirmation' => 'TempPass1!',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_forced_password_change_rejects_reusing_temporary_password(): void
    {
        $user = $this->makeUser(mustChangePassword: true);

        $this->actingAs($user)
            ->put(route('password.force.update'), [
                'current_password' => 'TempPass1!',
                'password' => 'TempPass1!',
                'password_confirmation' => 'TempPass1!',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_code_verified_user_can_set_a_password_without_a_temporary_password(): void
    {
        $user = $this->makeUser(mustChangePassword: true);

        $this->actingAs($user)
            ->withSession(['registration_verified_user_id' => $user->id])
            ->get(route('password.force.edit'))
            ->assertOk()
            ->assertDontSee('name="current_password"', false);

        $this->actingAs($user)
            ->withSession(['registration_verified_user_id' => $user->id])
            ->put(route('password.force.update'), [
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NewPass2!', $user->password));
        $this->assertGuest();
    }

    public function test_successful_forced_password_change_clears_flag_and_logs_user_out(): void
    {
        $user = $this->makeUser(mustChangePassword: true);

        $this->actingAs($user)
            ->put(route('password.force.update'), [
                'current_password' => 'TempPass1!',
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NewPass2!', $user->password));
        $this->assertGuest();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'TempPass1!',
        ])->assertSessionHasErrors();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'NewPass2!',
        ])->assertRedirect('/dashboard');
    }

    public function test_unflagged_user_can_access_dashboard_normally(): void
    {
        $user = $this->makeUser(mustChangePassword: false);

        $this->actingAs($user)
            ->get(route('dashboard.redirect'))
            ->assertRedirect($user->dashboardPath());
    }

    private function makeUser(bool $mustChangePassword): User
    {
        $user = User::factory()->create([
            'user_type' => 'student',
            'email_verified_at' => now(),
            'password' => 'TempPass1!',
            'must_change_password' => $mustChangePassword,
        ]);
        $user->roles()->attach(Role::where('name', 'student')->value('id'));

        return $user;
    }
}
