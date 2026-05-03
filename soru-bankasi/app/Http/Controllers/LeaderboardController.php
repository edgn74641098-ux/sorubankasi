<?php

namespace App\Http\Controllers;

use App\Models\LeaderboardGlobalSnapshot;
use App\Models\LeaderboardSubjectSnapshot;
use App\Models\Subject;
use App\Models\UserWrongQuestionStat;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;

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

        if ($globalSnapshotAt) {
            $globalRows = LeaderboardGlobalSnapshot::query()
                ->with('user:id,name')
                ->where('snapshot_at', $globalSnapshotAt)
                ->orderBy('rank')
                ->limit(100)
                ->get();

            $myGlobalRank = LeaderboardGlobalSnapshot::query()
                ->where('snapshot_at', $globalSnapshotAt)
                ->where('user_id', auth()->id())
                ->first();
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
            'wrongQuestionCount' => $wrongQuestionCount,
            'leaderboardMinTests' => $leaderboardMinTests,
            'leaderboardWindowDays' => 30,
        ]);
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
            ->limit(100)
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
            ->limit(100)
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
}
