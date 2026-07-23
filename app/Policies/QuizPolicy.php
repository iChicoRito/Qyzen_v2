<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

// G6: educator ownership only (questions are authored/owned by the educator). Students never
// reach quiz CRUD; admins don't browse questions. correct_answer stays hidden on the model.
class QuizPolicy
{
    private function visibleTo(User $user, Quiz $quiz): bool
    {
        return Quiz::withTrashed()->visibleTo($user)->whereKey($quiz->getKey())->exists();
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('educator');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->hasRole('educator') && $this->visibleTo($user, $quiz);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('educator');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->hasRole('educator') && $this->visibleTo($user, $quiz);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }

    public function restore(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }
}
