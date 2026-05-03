<?php

namespace App\Services;

use App\Models\LeaderboardGlobalSnapshot;
use App\Models\LeaderboardSubjectSnapshot;
use App\Models\Subject;
use App\Models\Test;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LeaderboardSnapshotService
{
    public function generate(?CarbonImmutable $snapshotAt = null): void
    {
        $snapshotAt ??= CarbonImmutable::now()->startOfMinute();
        $windowStart = $snapshotAt->subDays(30);

        DB::transaction(function () use ($snapshotAt, $windowStart) {
            $globalRows = $this->buildGlobalRows($windowStart);
            $subjectRows = $this->buildSubjectRows($windowStart);

            LeaderboardGlobalSnapshot::query()
                ->where('snapshot_at', $snapshotAt)
                ->delete();
            LeaderboardSubjectSnapshot::query()
                ->where('snapshot_at', $snapshotAt)
                ->delete();

            if (! empty($globalRows)) {
                LeaderboardGlobalSnapshot::query()->insert(
                    array_map(fn (array $row) => $row + [
                        'snapshot_at' => $snapshotAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], $globalRows)
                );
            }

            if (! empty($subjectRows)) {
                LeaderboardSubjectSnapshot::query()->insert(
                    array_map(fn (array $row) => $row + [
                        'snapshot_at' => $snapshotAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], $subjectRows)
                );
            }
        });
    }

    private function buildGlobalRows(CarbonImmutable $windowStart): array
    {
        $rows = Test::query()
            ->selectRaw('user_id, SUM(score) as total_score, SUM(wrong_count) as total_wrong, MIN(ended_at) as first_finish')
            ->where('status', 'finished')
            ->where('aborted', false)
            ->where('question_count', 20)
            ->where('duration_minutes', '<=', 30)
            ->where('ended_at', '>=', $windowStart)
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('total_score')
            ->orderBy('total_wrong')
            ->orderBy('first_finish')
            ->get();

        $rank = 1;
        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'user_id' => (int) $row->user_id,
                'score' => (int) $row->total_score,
                'rank' => $rank++,
            ];
        }

        return $payload;
    }

    private function buildSubjectRows(CarbonImmutable $windowStart): array
    {
        $payload = [];

        Subject::query()->where('is_active', true)->pluck('id')->each(function (int $subjectId) use (&$payload, $windowStart) {
            $rows = Test::query()
                ->selectRaw('user_id, SUM(score) as total_score, SUM(wrong_count) as total_wrong, MIN(ended_at) as first_finish')
                ->where('status', 'finished')
                ->where('aborted', false)
                ->where('question_count', 20)
                ->where('duration_minutes', '<=', 30)
                ->where('subject_id', $subjectId)
                ->where('ended_at', '>=', $windowStart)
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) >= 3')
                ->orderByDesc('total_score')
                ->orderBy('total_wrong')
                ->orderBy('first_finish')
                ->get();

            $rank = 1;
            foreach ($rows as $row) {
                $payload[] = [
                    'subject_id' => $subjectId,
                    'user_id' => (int) $row->user_id,
                    'score' => (int) $row->total_score,
                    'rank' => $rank++,
                ];
            }
        });

        return $payload;
    }
}

