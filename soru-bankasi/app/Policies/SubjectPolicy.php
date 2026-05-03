<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Subject $subject): bool
    {
        return $user->exists;
    }

    public function startTest(User $user, Subject $subject): bool
    {
        return $user->exists && $subject->is_active;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->isAdmin();
    }
}