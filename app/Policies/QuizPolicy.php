<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

// G6: educator ownership only (questions are authored/owned by the educator). Students never
// reach quiz CRUD; admins don't browse questions. correct_answer stays hidden on the model.
class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('educator');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->hasRole('educator') && $quiz->educator_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('educator');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->hasRole('educator') && $quiz->educator_id === $user->id;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }
}
