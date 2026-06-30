<?php

namespace App\Policies;

use App\Models\GroupChat;
use App\Models\User;

// G10: educator owns chats (create/delete). Students participate only in chats for subjects
// they're enrolled in (checked in the controller via enrollment) — they cannot create/delete.
class GroupChatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('educator') || $user->hasRole('student');
    }

    public function view(User $user, GroupChat $chat): bool
    {
        if ($user->hasRole('educator')) {
            return $chat->educator_id === $user->id;
        }

        // student: enrolled in the chat's subject with the chat's educator.
        return \App\Models\Enrolled::where('student_id', $user->id)
            ->where('subject_id', $chat->subject_id)
            ->where('educator_id', $chat->educator_id)
            ->where('is_active', true)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('educator');
    }

    public function delete(User $user, GroupChat $chat): bool
    {
        return $user->hasRole('educator') && $chat->educator_id === $user->id;
    }
}
