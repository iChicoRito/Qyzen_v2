<?php

namespace App\Policies;

use App\Models\Enrolled;
use App\Models\User;

// G4: educator ownership (full) / student view-own. Admin full.
class EnrolledPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('educator') || $user->hasRole('student');
    }

    public function view(User $user, Enrolled $enrolled): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('educator')) {
            return $enrolled->educator_id === $user->id;
        }

        return $enrolled->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('educator');
    }

    public function update(User $user, Enrolled $enrolled): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $enrolled->educator_id === $user->id);
    }

    public function delete(User $user, Enrolled $enrolled): bool
    {
        return $this->update($user, $enrolled);
    }
}
