<?php

namespace App\Policies;

use App\Models\QuestionReport;
use App\Models\User;

class QuestionReportPolicy
{
    /**
     * Determine whether the user can view any question report.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    /**
     * Determine whether the user can view the question report.
     */
    public function view(User $user, QuestionReport $report): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    /**
     * Determine whether the user can update the question report.
     */
    public function update(User $user, QuestionReport $report): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    /**
     * Determine whether the user can delete the question report.
     */
    public function delete(User $user, QuestionReport $report): bool
    {
        return $user->isAdmin();
    }
}
