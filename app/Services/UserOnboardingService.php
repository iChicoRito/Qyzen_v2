<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserOnboardingService
{
    public function send(User $user, bool $mail = true): string
    {
        if (! $mail) {
            $temporaryPassword = Str::password(12, true, true, false, false);

            $user->password = $temporaryPassword;
            $user->must_change_password = true;
            $user->save();

            return $temporaryPassword;
        }

        $verificationCode = (string) random_int(100000, 999999);
        $user->forceFill([
            'must_change_password' => true,
            'email_verification_code' => Hash::make($verificationCode),
            'email_verification_code_expires_at' => now()->addDays(7),
        ])->save();

        $user->notify(new AccountCreatedNotification($verificationCode));

        return $verificationCode;
    }
}
