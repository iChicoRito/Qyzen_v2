<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserImport;

class UserImportPolicy
{
    public function view(User $user, UserImport $userImport): bool
    {
        return $user->hasRole('admin') && $userImport->initiated_by_user_id === $user->id;
    }
}
