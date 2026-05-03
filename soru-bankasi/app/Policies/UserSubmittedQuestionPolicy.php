<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSubmittedQuestion;

class UserSubmittedQuestionPolicy
{
    public function reviewSubmissions(User $user): bool
    {
        return $user->role?->name === 'admin' || $user->role?->name === 'editor';
    }

    public function revokeApproval(User $user): bool
    {
        return $user->role?->name === 'admin';
    }
}
