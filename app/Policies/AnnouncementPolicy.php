<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('educator') || $user->hasRole('student');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if ($user->hasRole('educator')) {
            return $announcement->educator_id === $user->id;
        }

        return Announcement::whereKey($announcement->id)->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('educator');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->hasRole('educator') && $announcement->educator_id === $user->id;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }
}
