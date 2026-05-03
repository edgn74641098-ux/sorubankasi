<?php

namespace App\Services;

use App\Models\Test;
use App\Models\UserSubjectStat;
use App\Models\UserWrongQuestionStat;
use App\Models\UserRecentQuestionHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TestFinalizeService
{
    public function finalize(Test $test): Test
    {
        if ($test->status === 'finished') {
            return $test->load(['items.question', 'subject', 'user']);
        }

        $result = $this->finalizeInTransaction($test);
        $this->regenerateLeaderboardSnapshotQuietly();

        return $result;
    }

    public function finalizeExpiredForUser(int $userId): void
    {
        $tests = Test::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('started_at', '<=', Carbon::now()->subMinutes(30))
            ->get();

        $finalizedAny = false;
        foreach ($tests as $test) {
            $this->finalizeInTransaction($test);
            $finalizedAny = true;
        }

        if ($finalizedAny) {
            $this->regenerateLeaderboardSnapshotQuietly();
        }
    }

    private function finalizeInTransaction(Test $test): Test
    {
        return DB::transaction(function () use ($test) {
            $test->loadMissing(['items.question', 'user', 'subject']);

            $correctCount = 0;
            $wrongCount = 0;
            $blankCount = 0;

            foreach ($test->items as $item) {
                $isCorrect = $item->user_answer !== null && $item->user_answer === $item->question->correct_option;
                $isBlank = $item->user_answer === null;
                $awardedPoints = $isCorrect ? 5 : 0;

                if ($isCorrect) {
                    $correctCount++;
                } elseif ($isBlank) {
                    $blankCount++;
                } else {
                    $wrongCount++;
                }

                $item->update([
                    'is_correct' => $isCorrect,
                    'awarded_points' => $awardedPoints,
                ]);

                if ($isCorrect) {
                    $item->question->increment('correct_count');
                } elseif (! $isBlank) {
                    $item->question->increment('wrong_count');
                }

                if (! $isCorrect && ! $isBlank) {
                    $wrongStat = UserWrongQuestionStat::query()->firstOrNew([
                        'user_id' => $test->user_id,
                        'question_id' => $item->question_id,
                    ]);

                    $wrongStat->wrong_count = (int) $wrongStat->wrong_count + 1;
                    $wrongStat->last_wrong_at = Carbon::now();
                    $wrongStat->save();
                }

                $recentHistory = UserRecentQuestionHistory::query()->firstOrNew([
                    'user_id' => $test->user_id,
                    'question_id' => $item->question_id,
                ]);

                $recentHistory->last_answered_at = Carbon::now();
                $recentHistory->save();
            }

            $score = min(100, $correctCount * 5);

            $test->update([
                'score' => $score,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'blank_count' => $blankCount,
                'status' => 'finished',
                'ended_at' => Carbon::now(),
            ]);

            $subjectStat = UserSubjectStat::query()->firstOrNew([
                'user_id' => $test->user_id,
                'subject_id' => $test->subject_id,
            ]);

            $subjectStat->solved_count = (int) $subjectStat->solved_count + $test->question_count;
            $subjectStat->correct_count = (int) $subjectStat->correct_count + $correctCount;
            $subjectStat->wrong_count = (int) $subjectStat->wrong_count + $wrongCount;
            $subjectStat->blank_count = (int) $subjectStat->blank_count + $blankCount;
            $subjectStat->total_score = (int) $subjectStat->total_score + $score;
            $subjectStat->last_test_at = Carbon::now();
            $subjectStat->save();

            $test->user->increment('total_score', $score);

            return $test->fresh(['items.question', 'subject', 'user']);
        });
    }

    private function regenerateLeaderboardSnapshotQuietly(): void
    {
        try {
            app(LeaderboardSnapshotService::class)->generate();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}