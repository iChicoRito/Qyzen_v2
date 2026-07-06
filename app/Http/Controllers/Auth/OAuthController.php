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

    // Self-service email change: send the (logged-in) user to Google to pick an account; the
    // callback below reads the 'change_email' intent and swaps their sign-in email to that account.
    public function changeEmailRedirect()
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('profile.edit')->with('error', 'Google sign-in is not configured yet.');
        }

        session(['oauth_intent' => 'change_email']);

        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(string $provider)
    {
        abort_unless($provider === 'google', 404);

        $oauthUser = Socialite::driver('google')->user();
        $email = Str::lower((string) $oauthUser->getEmail());

        // Email-change flow: the user is already signed in and asked to switch their sign-in email.
        if (session('oauth_intent') === 'change_email') {
            session()->forget('oauth_intent');

            return $this->applyEmailChange($email);
        }

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

    private function applyEmailChange(string $email)
    {
        $user = auth()->user();

        // Lost the session mid-flow — bounce to login rather than mutate anything.
        if (! $user) {
            return redirect()->route('login');
        }

        if ($email === Str::lower((string) $user->email)) {
            return redirect()->route('profile.edit')
                ->with('error', 'That Google account is already your email. Pick a different one to switch.');
        }

        if (User::where('email', $email)->whereKeyNot($user->getKey())->exists()) {
            return redirect()->route('profile.edit')
                ->with('error', 'That Google email is already used by another account.');
        }

        $user->email = $email;
        $user->save();

        return redirect()->route('profile.edit')
            ->with('status', 'This Google email is now your sign-in email.');
    }
}
