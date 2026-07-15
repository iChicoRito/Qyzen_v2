<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountActivationController extends Controller
{
    public function __invoke(User $user): View|RedirectResponse
    {
        abort_unless(request()->hasValidSignature(), 403);

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Account already verified.');
        }

        return view('auth.verification-code', ['email' => $user->email]);
    }

    public function verify(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $verified = DB::transaction(function () use ($user, $data): ?User {
            $user = User::query()->lockForUpdate()->find($user->id);

            if (! $user || ! $user->is_active || $user->hasVerifiedEmail() || ! $user->email_verification_code_expires_at?->isFuture()
                || ! Hash::check($data['code'], $user->email_verification_code ?? '')) {
                return null;
            }

            $user->forceFill([
                'email_verified_at' => now(),
                'email_verification_code' => null,
                'email_verification_code_expires_at' => null,
            ])->save();

            return $user;
        });

        if (! $verified) {
            return back()->withErrors(['code' => 'The verification code is invalid or expired.'])->onlyInput('code');
        }

        event(new Verified($verified));
        Auth::login($verified);
        $request->session()->regenerate();
        $request->session()->put('registration_verified_user_id', $verified->id);

        return redirect()->route('password.force.edit');
    }
}
