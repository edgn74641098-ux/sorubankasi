<?php

namespace App\Http\Controllers;

use App\Models\LeaderboardGlobalSnapshot;
use App\Models\LeaderboardSubjectSnapshot;
use App\Models\Subject;
use App\Models\UserWrongQuestionStat;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {
    }

    public function index(): View
    {
        $leaderboardMinTests = max(1, min(20, $this->settingsService->getInt('minimum_leaderboard_tests', 3)));
        $globalSnapshotAt = LeaderboardGlobalSnapshot::query()->max('snapshot_at');

        $globalRows = collect();
        $myGlobalRank = null;
        $globalLeader = null;
        $globalPointsToNext = null;
        $globalAccuracy = null;

        if ($globalSnapshotAt) {
            $globalRows = LeaderboardGlobalSnapshot::query()
                ->with('user:id,name')
                ->where('snapshot_at', $globalSnapshotAt)
                ->orderBy('rank')
                ->limit($this->globalLimit())
                ->get();

            $myGlobalRank = LeaderboardGlobalSnapshot::query()
                ->where('snapshot_at', $globalSnapshotAt)
                ->where('user_id', auth()->id())
                ->first();

            $globalLeader = $globalRows->first();

            if ($myGlobalRank) {
                $above = $globalRows->firstWhere('rank', $myGlobalRank->rank - 1);
                $globalPointsToNext = $above ? max(0, (int) $above->score - (int) $myGlobalRank->score + 1) : 0;
                $globalAccuracy = $this->accuracy((int) $myGlobalRank->correct_total, (int) $myGlobalRank->questions_total);
            }
        }

        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedSubjectId = request()->integer('subject_id');
        if (! $selectedSubjectId && $subjects->isNotEmpty()) {
            $selectedSubjectId = (int) $subjects->first()->id;
        }

        $subjectSnapshotAt = null;
        $subjectRows = collect();
        $mySubjectRank = null;
        $subjectLeader = null;
        $subjectPointsToNext = null;
        $subjectAccuracy = null;
        $wrongQuestionCount = 0;

        if ($selectedSubjectId) {
            $subjectSnapshotAt = LeaderboardSubjectSnapshot::query()
                ->where('subject_id', $selectedSubjectId)
                ->max('snapshot_at');

            $wrongQuestionCount = UserWrongQuestionStat::query()
                ->where('user_id', auth()->id())
                ->whereHas('question', function ($query) use ($selectedSubjectId) {
                    $query->where('subject_id', $selectedSubjectId)
                        ->where('status', 'active');
                })
                ->count();

            if ($subjectSnapshotAt) {
                $subjectRows = LeaderboardSubjectSnapshot::query()
                    ->with('user:id,name')
                    ->where('subject_id', $selectedSubjectId)
                    ->where('snapshot_at', $subjectSnapshotAt)
                    ->orderBy('rank')
                    ->limit(100)
                    ->get();

                $mySubjectRank = LeaderboardSubjectSnapshot::query()
                    ->where('subject_id', $selectedSubjectId)
                    ->where('snapshot_at', $subjectSnapshotAt)
                    ->where('user_id', auth()->id())
                    ->first();

                $subjectLeader = $subjectRows->first();

                if ($mySubjectRank) {
                    $above = $subjectRows->firstWhere('rank', $mySubjectRank->rank - 1);
                    $subjectPointsToNext = $above ? max(0, (int) $above->score - (int) $mySubjectRank->score + 1) : 0;
                    $subjectAccuracy = $this->accuracy((int) $mySubjectRank->correct_total, (int) $mySubjectRank->questions_total);
                }
            }
        }

        return view('leaderboard.index', [
            'subjects' => $subjects,
            'selectedSubjectId' => $selectedSubjectId,
            'globalSnapshotAt' => $globalSnapshotAt,
            'subjectSnapshotAt' => $subjectSnapshotAt,
            'globalRows' => $globalRows,
            'subjectRows' => $subjectRows,
            'myGlobalRank' => $myGlobalRank,
            'mySubjectRank' => $mySubjectRank,
            'globalLeader' => $globalLeader,
            'subjectLeader' => $subjectLeader,
            'globalPointsToNext' => $globalPointsToNext,
            'subjectPointsToNext' => $subjectPointsToNext,
            'globalAccuracy' => $globalAccuracy,
            'subjectAccuracy' => $subjectAccuracy,
            'wrongQuestionCount' => $wrongQuestionCount,
            'leaderboardMinTests' => $leaderboardMinTests,
            'leaderboardWindowDays' => 30,
            'weeklyLeaders' => $this->weeklyLeaders($this->weeklyLimit()),
            'mostImprovedRows' => $this->mostImprovedRows($this->formLimit()),
        ]);
    }

    private function weeklyLeaders(int $limit)
    {
        return DB::table('tests')
            ->join('users', 'users.id', '=', 'tests.user_id')
            ->selectRaw('users.id, users.name, COALESCE(SUM(tests.score), 0) as score_total, COUNT(*) as test_count')
            ->where('tests.status', 'finished')
            ->where('tests.ended_at', '>=', now()->subDays(7))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('score_total')
            ->limit($limit)
            ->get();
    }

    private function mostImprovedRows(int $limit)
    {
        return DB::table('tests')
            ->join('users', 'users.id', '=', 'tests.user_id')
            ->selectRaw('users.id, users.name, COUNT(*) as test_count, COALESCE(SUM(tests.score), 0) as score_total, COALESCE(AVG(tests.score), 0) as average_score')
            ->where('tests.status', 'finished')
            ->where('tests.ended_at', '>=', now()->subDays(7))
            ->groupBy('users.id', 'users.name')
            ->having('test_count', '>=', 2)
            ->orderByDesc('average_score')
            ->limit($limit)
            ->get();
    }

    private function accuracy(int $correct, int $total): ?float
    {
        if ($total <= 0) {
            return null;
        }

        return round(($correct / $total) * 100, 1);
    }

    public function apiIndex(): JsonResponse
    {
        $snapshotAt = LeaderboardGlobalSnapshot::query()->max('snapshot_at');

        if (! $snapshotAt) {
            return response()->json([
                'snapshot_at' => null,
                'rows' => [],
                'my_rank' => null,
            ]);
        }

        $rows = LeaderboardGlobalSnapshot::query()
            ->with('user:id,name')
            ->where('snapshot_at', $snapshotAt)
            ->orderBy('rank')
            ->limit($this->globalLimit())
            ->get()
            ->map(fn (LeaderboardGlobalSnapshot $row) => [
                'rank' => $row->rank,
                'user_id' => $row->user_id,
                'user_name' => $row->user?->name,
                'score' => $row->score,
                'questions_total' => (int) $row->questions_total,
                'correct_total' => (int) $row->correct_total,
                'wrong_total' => (int) $row->wrong_total,
            ]);

        $myRank = LeaderboardGlobalSnapshot::query()
            ->where('snapshot_at', $snapshotAt)
            ->where('user_id', auth()->id())
            ->first();

        return response()->json([
            'snapshot_at' => $snapshotAt,
            'rows' => $rows,
            'my_rank' => $myRank ? [
                'rank' => $myRank->rank,
                'score' => $myRank->score,
                'questions_total' => (int) $myRank->questions_total,
                'correct_total' => (int) $myRank->correct_total,
                'wrong_total' => (int) $myRank->wrong_total,
            ] : null,
        ]);
    }

    public function apiSubject(Subject $subject): JsonResponse
    {
        abort_unless($subject->is_active, 404);

        $snapshotAt = LeaderboardSubjectSnapshot::query()
            ->where('subject_id', $subject->id)
            ->max('snapshot_at');

        if (! $snapshotAt) {
            return response()->json([
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'slug' => $subject->slug,
                ],
                'snapshot_at' => null,
                'rows' => [],
                'my_rank' => null,
            ]);
        }

        $rows = LeaderboardSubjectSnapshot::query()
            ->with('user:id,name')
            ->where('subject_id', $subject->id)
            ->where('snapshot_at', $snapshotAt)
            ->orderBy('rank')
            ->limit($this->globalLimit())
            ->get()
            ->map(fn (LeaderboardSubjectSnapshot $row) => [
                'rank' => $row->rank,
                'user_id' => $row->user_id,
                'user_name' => $row->user?->name,
                'score' => $row->score,
                'questions_total' => (int) $row->questions_total,
                'correct_total' => (int) $row->correct_total,
                'wrong_total' => (int) $row->wrong_total,
            ]);

        $myRank = LeaderboardSubjectSnapshot::query()
            ->where('subject_id', $subject->id)
            ->where('snapshot_at', $snapshotAt)
            ->where('user_id', auth()->id())
            ->first();

        return response()->json([
            'subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
                'slug' => $subject->slug,
            ],
            'snapshot_at' => $snapshotAt,
            'rows' => $rows,
            'my_rank' => $myRank ? [
                'rank' => $myRank->rank,
                'score' => $myRank->score,
                'questions_total' => (int) $myRank->questions_total,
                'correct_total' => (int) $myRank->correct_total,
                'wrong_total' => (int) $myRank->wrong_total,
            ] : null,
        ]);
    }

    private function globalLimit(): int
    {
        return max(1, min(100, $this->settingsService->getInt('leaderboard_global_limit', 20)));
    }

    private function weeklyLimit(): int
    {
        return max(1, min(50, $this->settingsService->getInt('leaderboard_weekly_limit', 5)));
    }

    private function formLimit(): int
    {
        return max(1, min(50, $this->settingsService->getInt('leaderboard_form_limit', 5)));
    }
}
