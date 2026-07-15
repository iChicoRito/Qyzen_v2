<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Services\UserOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountVerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'student', 'description' => 'student', 'is_active' => true]);
    }

    public function test_signed_account_link_accepts_code_then_starts_password_setup(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $user->roles()->attach(Role::where('name', 'student')->value('id'));
        app(UserOnboardingService::class)->send($user);
        $notification = Notification::sent($user, AccountCreatedNotification::class)->first();
        $url = URL::temporarySignedRoute('account.activate', now()->addDays(7), ['user' => $user]);

        $this->assertNotNull($notification);

        $this->get($url)
            ->assertOk()
            ->assertSee('Verify your account');

        $this->post($url, ['code' => $notification->verificationCode])
            ->assertRedirect(route('password.force.edit'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_invalid_or_expired_code_does_not_activate_the_account(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'email_verification_code' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->subSecond(),
        ]);
        $url = URL::temporarySignedRoute('account.activate', now()->addDays(7), ['user' => $user]);

        $this->post($url, ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertGuest();
    }

    public function test_consumed_code_cannot_be_used_again(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'email_verification_code' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->addDay(),
        ]);
        $url = URL::temporarySignedRoute('account.activate', now()->addDays(7), ['user' => $user]);

        $this->post($url, ['code' => '123456'])->assertRedirect(route('password.force.edit'));
        $this->post($url, ['code' => '123456'])->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verification_code);
    }

    public function test_inactive_account_cannot_activate_or_start_a_session(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_active' => false,
            'email_verification_code' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->addDay(),
        ]);
        $url = URL::temporarySignedRoute('account.activate', now()->addDays(7), ['user' => $user]);

        $this->post($url, ['code' => '123456'])->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertGuest();
    }

    public function test_code_from_another_account_cannot_activate_the_signed_account(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'email_verification_code' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->addDay(),
        ]);
        $other = User::factory()->create([
            'email_verified_at' => null,
            'email_verification_code' => Hash::make('654321'),
            'email_verification_code_expires_at' => now()->addDay(),
        ]);
        $url = URL::temporarySignedRoute('account.activate', now()->addDays(7), ['user' => $user]);

        $this->post($url, ['code' => '654321'])->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($other->fresh()->email_verified_at);
    }

    public function test_expired_signed_link_is_rejected_before_showing_the_code_form(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('account.activate', now()->addSecond(), ['user' => $user]);

        $this->travel(2)->seconds();

        $this->get($url)->assertForbidden();
    }
}
