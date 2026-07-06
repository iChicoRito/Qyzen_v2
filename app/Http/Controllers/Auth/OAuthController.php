<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

// C3: Google OAuth via Socialite — reproduces the source (auth)/auth/callback:
// existing-email check -> not-registered / inactive flows. Identity binds by lowercased email.
class OAuthController extends Controller
{
    public function redirect(string $provider)
    {
        abort_unless($provider === 'google', 404);

        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in is not configured yet.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(string $provider)
    {
        abort_unless($provider === 'google', 404);

        $oauthUser = Socialite::driver('google')->user();
        $email = Str::lower((string) $oauthUser->getEmail());

        $user = User::where('email', $email)->first();

        // Qyzen does not self-provision via OAuth — accounts are admin-created.
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'This email is not registered.']);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors(['email' => 'This account is inactive.']);
        }

        auth()->login($user, remember: true);

        return redirect()->intended(route('dashboard.redirect'));
    }
}
