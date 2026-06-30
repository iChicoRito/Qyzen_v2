<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

// F6: admin-only RBAC permission management.
class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasRole('admin');
    }
}
