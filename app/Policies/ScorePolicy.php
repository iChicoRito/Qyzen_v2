<?php

namespace App\Policies;

use App\Models\Score;
use App\Models\User;

// D3: mirror of tbl_scores RLS — admin full / educator ownership (view) /
// student own (view + create/update of own attempt). Educators cannot edit scores.
class ScorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('educator') || $user->hasRole('student');
    }

    public function view(User $user, Score $score): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('educator')) {
            return $score->educator_id === $user->id;
        }

        return $score->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, Score $score): bool
    {
        // admin may correct; student may update only their own in-progress attempt.
        return $user->hasRole('admin')
            || ($user->hasRole('student') && $score->student_id === $user->id);
    }
}
