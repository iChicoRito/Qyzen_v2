<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

// F7: admin-only academic-year management.
class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, AcademicYear $year): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, AcademicYear $year): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, AcademicYear $year): bool
    {
        return $user->hasRole('admin');
    }
}
