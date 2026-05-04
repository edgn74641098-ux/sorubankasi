<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\QuestionImportBatch;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use App\Models\UserSubmittedQuestion;
use App\Services\QualityQueueService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly QualityQueueService $qualityQueueService
    ) {
    }

    public function __invoke(): View
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'subjects' => Subject::query()->whereNull('archived_at')->count(),
                'active_questions' => Question::query()->where('status', 'active')->count(),
                'draft_questions' => Question::query()->where('status', 'draft')->count(),
                'archived_questions' => Question::query()->where('status', 'archived')->count(),
                'pending_submissions' => UserSubmittedQuestion::query()->where('status', 'pending')->count(),
                'tests_today' => Test::query()->whereDate('created_at', $today)->count(),
                'imports_pending' => QuestionImportBatch::query()->where('status', 'preview')->count(),
            ],
            'todayStats' => [
                'tests_started' => Test::query()->whereDate('created_at', $today)->count(),
                'tests_finished' => Test::query()->whereDate('ended_at', $today)->where('status', 'finished')->count(),
                'avg_score' => round((float) Test::query()->whereDate('ended_at', $today)->where('status', 'finished')->avg('score'), 1),
                'new_users' => User::query()->whereDate('created_at', $today)->count(),
            ],
            'weekStats' => [
                'tests_finished' => Test::query()->whereDate('ended_at', '>=', $weekStart)->where('status', 'finished')->count(),
                'avg_score' => round((float) Test::query()->whereDate('ended_at', '>=', $weekStart)->where('status', 'finished')->avg('score'), 1),
                'reports' => QuestionReport::query()->whereDate('created_at', '>=', $weekStart)->count(),
                'submissions' => UserSubmittedQuestion::query()->whereDate('created_at', '>=', $weekStart)->count(),
            ],
            'actionCenter' => [
                'pending_reports' => QuestionReport::query()->where('status', 'pending')->count(),
                'pending_submissions' => UserSubmittedQuestion::query()->where('status', 'pending')->count(),
                'imports_pending' => QuestionImportBatch::query()->where('status', 'preview')->count(),
                'archive_expiring' => Subject::query()
                    ->whereNotNull('archived_at')
                    ->where('purge_after', '<=', now()->addDay())
                    ->count()
                    + Question::query()
                        ->where('status', 'archived')
                        ->where('purge_after', '<=', now()->addDay())
                        ->count(),
                'quality_total' => $this->qualityQueueService->getTooEasyCount()
                    + $this->qualityQueueService->getTooHardCount()
                    + $this->qualityQueueService->getMostReportedCount(),
            ],
            'quality_queue' => [
                'too_easy_count' => $this->qualityQueueService->getTooEasyCount(),
                'too_easy_questions' => $this->qualityQueueService->getTooEasyQuestions(5),
                'too_hard_count' => $this->qualityQueueService->getTooHardCount(),
                'too_hard_questions' => $this->qualityQueueService->getTooHardQuestions(5),
                'reported_count' => $this->qualityQueueService->getMostReportedCount(),
                'reported_questions' => $this->qualityQueueService->getMostReportedQuestions(5),
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
            'recentReports' => QuestionReport::query()
                ->with(['user:id,name', 'question:id,subject_id,question_text', 'question.subject:id,name'])
                ->latest()
                ->limit(6)
                ->get(),
            'recentAuditLogs' => AuditLog::query()
                ->with('actor:id,name')
                ->latest()
                ->limit(6)
                ->get(),
            'operationTrend' => $this->operationTrend(),
        ]);
    }

    private function operationTrend(): array
    {
        return collect(range(29, 0))
            ->map(function (int $daysAgo): array {
                $date = Carbon::today()->subDays($daysAgo);

                return [
                    'label' => $date->format('d.m'),
                    'tests' => Test::query()
                        ->whereDate('ended_at', $date->toDateString())
                        ->where('status', 'finished')
                        ->count(),
                    'questions' => (int) Test::query()
                        ->whereDate('ended_at', $date->toDateString())
                        ->where('status', 'finished')
                        ->sum('question_count'),
                ];
            })
            ->all();
    }
}
