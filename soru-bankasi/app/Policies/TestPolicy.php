<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;

class TestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Test $test): bool
    {
        return $user->id === $test->user_id;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function answer(User $user, Test $test): bool
    {
        return $user->id === $test->user_id;
    }

    public function finish(User $user, Test $test): bool
    {
        return $user->id === $test->user_id;
    }

    public function review(User $user, Test $test): bool
    {
        return $user->id === $test->user_id;
    }
}