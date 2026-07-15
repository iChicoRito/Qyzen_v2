<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function edit(): View
    {
        return view('auth.force-password-change', [
            'requiresCurrentPassword' => $this->requiresCurrentPassword(request()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $requiresCurrentPassword = $this->requiresCurrentPassword($request);

        $data = $request->validate([
            'current_password' => $requiresCurrentPassword
                ? ['required', 'string', 'current_password:web']
                : ['nullable', 'string'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();

        Auth::logout();
        $request->session()->forget('registration_verified_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Password changed. Sign in again with your new password.');
    }

    private function requiresCurrentPassword(Request $request): bool
    {
        return (int) $request->session()->get('registration_verified_user_id') !== $request->user()->id;
    }
}
