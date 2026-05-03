<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function view(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function update(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function delete(User $user, Question $question): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }
}