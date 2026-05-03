<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use App\Models\UserSubmittedQuestion;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'subjects' => Subject::query()->count(),
                'active_questions' => Question::query()->where('status', 'active')->count(),
                'pending_submissions' => UserSubmittedQuestion::query()->where('status', 'pending')->count(),
                'tests_today' => Test::query()->whereDate('created_at', now()->toDateString())->count(),
                'imports_pending' => QuestionImportBatch::query()->where('status', 'preview')->count(),
            ],
            'recentTests' => Test::query()
                ->with(['user:id,name', 'subject:id,name'])
                ->latest()
                ->limit(8)
                ->get(),
            'recentSubmissions' => UserSubmittedQuestion::query()
                ->with(['user:id,name', 'subject:id,name'])
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
