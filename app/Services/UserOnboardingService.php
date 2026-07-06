<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Support\Str;

class UserOnboardingService
{
    public function send(User $user): void
    {
        $temporaryPassword = Str::password(12, true, true, false, false);

        $user->password = $temporaryPassword;
        $user->must_change_password = true;
        $user->save();

        $user->notify(new AccountCreatedNotification($temporaryPassword));
    }
}
