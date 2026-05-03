<?php

namespace App\Providers;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\UserSubmittedQuestion;
use App\Policies\QuestionPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TestPolicy;
use App\Policies\UserSubmittedQuestionPolicy;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Question::class => QuestionPolicy::class,
        Subject::class => SubjectPolicy::class,
        Test::class => TestPolicy::class,
        UserSubmittedQuestion::class => UserSubmittedQuestionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
