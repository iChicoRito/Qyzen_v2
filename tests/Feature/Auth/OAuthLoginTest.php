<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class OAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_returns_to_login_when_credentials_are_missing(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('oauth.redirect', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Google sign-in is not configured yet.']);
    }

    public function test_google_redirect_uses_socialite_when_credentials_are_configured(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth?client_id=google-client-id'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('oauth.redirect', 'google'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth?client_id=google-client-id');
    }

    public function test_google_callback_logs_in_existing_active_user_by_email(): void
    {
        $this->seedRoles();
        $user = User::factory()->create([
            'user_type' => 'student',
            'email' => 'student@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->roles()->attach(Role::where('name', 'student')->value('id'));

        $this->mockGoogleUser('STUDENT@example.com');

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('dashboard.redirect'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_google_callback_rejects_unknown_email(): void
    {
        $this->mockGoogleUser('unknown@example.com');

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'This email is not registered.']);

        $this->assertGuest();
    }

    public function test_google_callback_rejects_inactive_account(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'email_verified_at' => now(),
        ]);

        $this->mockGoogleUser('inactive@example.com');

        $this->get(route('oauth.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'This account is inactive.']);

        $this->assertGuest();
    }

    public function test_unsupported_oauth_provider_returns_not_found(): void
    {
        $this->get(route('oauth.redirect', 'github'))->assertNotFound();
        $this->get(route('oauth.callback', 'github'))->assertNotFound();
    }

    private function mockGoogleUser(string $email): void
    {
        $oauthUser = Mockery::mock();
        $oauthUser->shouldReceive('getEmail')->once()->andReturn($email);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($oauthUser);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }

    private function seedRoles(): void
    {
        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }
    }
}
