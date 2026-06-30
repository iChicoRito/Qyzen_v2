<?php

namespace App\Policies;

use App\Models\AcademicTerm;
use App\Models\User;

// F8: admin-only academic-term management.
class AcademicTermPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, AcademicTerm $term): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, AcademicTerm $term): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, AcademicTerm $term): bool
    {
        return $user->hasRole('admin');
    }
}
