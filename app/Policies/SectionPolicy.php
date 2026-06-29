<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

// D3: mirror of tbl_sections RLS — admin full / educator ownership + sections:* permission /
// student enrollment (view only).
class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $user->hasPermission('sections:view'))
            || $user->hasRole('student');
    }

    public function view(User $user, Section $section): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('educator')) {
            return $section->educator_id === $user->id && $user->hasPermission('sections:view');
        }

        return Section::whereKey($section->id)->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $user->hasPermission('sections:create'));
    }

    public function update(User $user, Section $section): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $section->educator_id === $user->id && $user->hasPermission('sections:update'));
    }

    public function delete(User $user, Section $section): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('educator') && $section->educator_id === $user->id && $user->hasPermission('sections:delete'));
    }
}
