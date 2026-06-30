<?php

namespace App\Policies;

use App\Models\LearningMaterial;
use App\Models\User;

// G9: educator ownership (full) / student enrollment-gated view (active only). No admin in source.
class LearningMaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('educator') || $user->hasRole('student');
    }

    public function view(User $user, LearningMaterial $material): bool
    {
        if ($user->hasRole('educator')) {
            return $material->educator_id === $user->id;
        }

        return LearningMaterial::whereKey($material->id)->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('educator');
    }

    public function update(User $user, LearningMaterial $material): bool
    {
        return $user->hasRole('educator') && $material->educator_id === $user->id;
    }

    public function delete(User $user, LearningMaterial $material): bool
    {
        return $this->update($user, $material);
    }
}
