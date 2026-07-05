<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Enrolled;
use App\Models\User;

// Task 30: private 1:1 messaging is enrollment-gated, subject-agnostic — any active
// tbl_enrolled row between the pair is enough. Re-checked on both create and every send.
class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('educator') || $user->hasRole('student');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->student_id === $user->id || $conversation->educator_id === $user->id;
    }

    public function create(User $user, User $otherParty): bool
    {
        $userIsStudent = $user->hasRole('student');

        if ($userIsStudent === $otherParty->hasRole('student')) {
            // both students or both educators — not a valid pairing.
            return false;
        }

        [$studentId, $educatorId] = $userIsStudent
            ? [$user->id, $otherParty->id]
            : [$otherParty->id, $user->id];

        return $this->enrolled($educatorId, $studentId);
    }

    /** Defense-in-depth: re-verified live on every send, not just at conversation creation. */
    public function send(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation)
            && $this->enrolled($conversation->educator_id, $conversation->student_id);
    }

    private function enrolled(int $educatorId, int $studentId): bool
    {
        return Enrolled::where('educator_id', $educatorId)
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->exists();
    }
}
