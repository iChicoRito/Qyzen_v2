<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

// D3: mirror of tbl_subjects RLS — admin full / educator ownership + subjects:* permission /
// student enrollment (view only).
class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $user->hasPermission('subjects:view'))
            || $user->hasRole('student');
    }

    public function view(User $user, Subject $subject): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('educator')) {
            return $subject->educator_id === $user->id && $user->hasPermission('subjects:view');
        }

        return Subject::whereKey($subject->id)->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $user->hasPermission('subjects:create'));
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $subject->educator_id === $user->id && $user->hasPermission('subjects:update'));
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $subject->educator_id === $user->id && $user->hasPermission('subjects:delete'));
    }
}
