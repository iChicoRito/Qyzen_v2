<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;

class AccountActivationController extends Controller
{
    public function __invoke(User $user): RedirectResponse
    {
        abort_unless(request()->hasValidSignature(), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('login')->with('status', 'Account confirmed. You can now sign in using the credentials sent to your email.');
    }
}
