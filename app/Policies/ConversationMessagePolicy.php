<?php

namespace App\Policies;

use App\Models\ConversationMessage;
use App\Models\Enrolled;
use App\Models\User;

class ConversationMessagePolicy
{
    public function update(User $user, ConversationMessage $message): bool
    {
        return $this->ownsAndEditable($user, $message);
    }

    public function delete(User $user, ConversationMessage $message): bool
    {
        return $this->ownsAndEditable($user, $message);
    }

    private function ownsAndEditable(User $user, ConversationMessage $message): bool
    {
        if ($message->sender_user_id !== $user->id || $message->isDeleted()) {
            return false;
        }

        $conversation = $message->conversation;

        return Enrolled::where('educator_id', $conversation->educator_id)
            ->where('student_id', $conversation->student_id)
            ->where('is_active', true)
            ->exists();
    }
}
