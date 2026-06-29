<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

// D3: mirror of tbl_assessments RLS — educator ownership (full) / student enrollment (view).
// Admin retains app-layer full access (no admin RLS policy existed, but admin is superuser here).
class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('educator') || $user->hasRole('student');
    }

    public function view(User $user, Assessment $assessment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('educator')) {
            return $assessment->educator_id === $user->id;
        }

        return Assessment::whereKey($assessment->id)->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('educator');
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $assessment->educator_id === $user->id);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $this->update($user, $assessment);
    }
}
