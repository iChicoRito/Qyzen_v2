<?php

namespace App\Policies;

use App\Models\User;

// F2/F4: admin-only management of tbl_users.
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasRole('admin');
    }
}
