<?php

namespace App\Http\Controllers;

use App\Models\LeaderboardGlobalSnapshot;
use App\Models\LeaderboardSubjectSnapshot;
use App\Models\QuestionReport;
use App\Models\Subject;
use App\Models\Test;
use App\Models\UserWrongQuestionStat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $globalRace = $this->globalRace($user->id);
        $bestSubjectRace = $this->bestSubjectRace($user->id);
        $weeklyStats = $this->weeklyStats($user->id);
        $topGlobalRows = $this->topGlobalRows();

        return view('dashboard', [
            'activeTest' => Test::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->latest('started_at')
                ->first(),
            'recentTests' => Test::query()
                ->with('subject:id,name')
                ->where('user_id', $user->id)
                ->where('status', 'finished')
                ->latest('ended_at')
                ->paginate(10, ['*'], 'recent_tests_page')
                ->withQueryString()
                ->fragment('son-testler'),
            'totalScore' => (int) $user->total_score,
            'globalRace' => $globalRace,
            'bestSubjectRace' => $bestSubjectRace,
            'weeklyStats' => $weeklyStats,
            'topGlobalRows' => $topGlobalRows,
            'dailyGoal' => $this->dailyGoal($globalRace, $weeklyStats),
            'weaknessSubject' => $this->weaknessSubject($user->id),
            'reportSummary' => $this->reportSummary($user->id),
            'performanceTrend' => $this->performanceTrend($user->id),
        ]);
    }

    private function globalRace(int $userId): array
    {
        $snapshotAt = LeaderboardGlobalSnapshot::query()->max('snapshot_at');

        if (! $snapshotAt) {
            return [
                'snapshot_at' => null,
                'my_rank' => null,
                'my_score' => 0,
                'accuracy' => null,
                'above' => null,
                'below' => null,
                'points_to_next' => null,
            ];
        }

        $myRank = LeaderboardGlobalSnapshot::query()
            ->where('snapshot_at', $snapshotAt)
            ->where('user_id', $userId)
            ->first();

        if (! $myRank) {
            return [
                'snapshot_at' => $snapshotAt,
                'my_rank' => null,
                'my_score' => 0,
                'accuracy' => null,
                'above' => null,
                'below' => null,
                'points_to_next' => null,
            ];
        }

        $above = LeaderboardGlobalSnapshot::query()
            ->with('user:id,name')
            ->where('snapshot_at', $snapshotAt)
            ->where('rank', $myRank->rank - 1)
            ->first();

        $below = LeaderboardGlobalSnapshot::query()
            ->with('user:id,name')
            ->where('snapshot_at', $snapshotAt)
            ->where('rank', $myRank->rank + 1)
            ->first();

        return [
            'snapshot_at' => $snapshotAt,
            'my_rank' => (int) $myRank->rank,
            'my_score' => (int) $myRank->score,
            'accuracy' => $this->accuracy((int) $myRank->correct_total, (int) $myRank->questions_total),
            'above' => $above,
            'below' => $below,
            'points_to_next' => $above ? max(0, (int) $above->score - (int) $myRank->score + 1) : 0,
            'progress_to_next' => $above ? min(100, round(((int) $myRank->score / max(1, (int) $above->score)) * 100)) : 100,
        ];
    }

    private function topGlobalRows()
    {
        $snapshotAt = LeaderboardGlobalSnapshot::query()->max('snapshot_at');

        if (! $snapshotAt) {
            return collect();
        }

        return LeaderboardGlobalSnapshot::query()
            ->with('user:id,name')
            ->where('snapshot_at', $snapshotAt)
            ->orderBy('rank')
            ->limit(3)
            ->get();
    }

    private function dailyGoal(array $globalRace, array $weeklyStats): array
    {
        if ($weeklyStats['test_count'] === 0) {
            return [
                'title' => 'Bugunun hedefi',
                'text' => 'Ilk testini tamamla ve haftalik puanini baslat.',
                'icon' => 'bi-flag',
                'button' => 'Test Baslat',
                'url' => route('subjects.index'),
            ];
        }

        if ($globalRace['points_to_next'] && $globalRace['points_to_next'] <= 100) {
            return [
                'title' => 'Bugunun hedefi',
                'text' => "{$globalRace['points_to_next']} puan alirsan bir ust sirayi zorlayabilirsin.",
                'icon' => 'bi-lightning-charge',
                'button' => 'Hemen Test Coz',
                'url' => route('subjects.index'),
            ];
        }

        if ($globalRace['my_rank'] === 1) {
            return [
                'title' => 'Bugunun hedefi',
                'text' => 'Zirvedesin. Bir test daha cozup farki ac.',
                'icon' => 'bi-trophy',
                'button' => 'Farki Ac',
                'url' => route('subjects.index'),
            ];
        }

        return [
            'title' => 'Bugunun hedefi',
            'text' => 'Bir test daha tamamla, haftalik serini guclendir.',
            'icon' => 'bi-fire',
            'button' => 'Teste Git',
            'url' => route('subjects.index'),
        ];
    }

    private function bestSubjectRace(int $userId): ?array
    {
        $snapshotAt = LeaderboardSubjectSnapshot::query()->max('snapshot_at');

        if (! $snapshotAt) {
            return null;
        }

        $row = LeaderboardSubjectSnapshot::query()
            ->with('subject:id,name')
            ->where('snapshot_at', $snapshotAt)
            ->where('user_id', $userId)
            ->orderBy('rank')
            ->first();

        if (! $row) {
            return null;
        }

        $above = LeaderboardSubjectSnapshot::query()
            ->with('user:id,name')
            ->where('snapshot_at', $snapshotAt)
            ->where('subject_id', $row->subject_id)
            ->where('rank', $row->rank - 1)
            ->first();

        return [
            'subject' => $row->subject,
            'rank' => (int) $row->rank,
            'score' => (int) $row->score,
            'accuracy' => $this->accuracy((int) $row->correct_total, (int) $row->questions_total),
            'points_to_next' => $above ? max(0, (int) $above->score - (int) $row->score + 1) : 0,
        ];
    }

    private function weeklyStats(int $userId): array
    {
        $stats = Test::query()
            ->selectRaw('COUNT(*) as test_count, COALESCE(SUM(score), 0) as score_total, COALESCE(SUM(correct_count), 0) as correct_total, COALESCE(SUM(question_count), 0) as question_total')
            ->where('user_id', $userId)
            ->where('status', 'finished')
            ->where('ended_at', '>=', Carbon::now()->subDays(7))
            ->first();

        return [
            'test_count' => (int) $stats->test_count,
            'score_total' => (int) $stats->score_total,
            'accuracy' => $this->accuracy((int) $stats->correct_total, (int) $stats->question_total),
        ];
    }

    private function performanceTrend(int $userId): array
    {
        return collect(range(29, 0))
            ->map(function (int $daysAgo) use ($userId): array {
                $date = Carbon::today()->subDays($daysAgo);

                $stats = Test::query()
                    ->selectRaw('COUNT(*) as test_count, COALESCE(SUM(question_count), 0) as question_total, COALESCE(SUM(correct_count), 0) as correct_total, COALESCE(SUM(wrong_count), 0) as wrong_total')
                    ->where('user_id', $userId)
                    ->where('status', 'finished')
                    ->whereDate('ended_at', $date->toDateString())
                    ->first();

                $questions = (int) $stats->question_total;
                $correct = (int) $stats->correct_total;

                return [
                    'label' => $date->format('d.m'),
                    'tests' => (int) $stats->test_count,
                    'questions' => $questions,
                    'correct' => $correct,
                    'wrong' => (int) $stats->wrong_total,
                    'accuracy' => $this->accuracy($correct, $questions),
                ];
            })
            ->all();
    }

    private function weaknessSubject(int $userId): ?array
    {
        $row = UserWrongQuestionStat::query()
            ->selectRaw('questions.subject_id, COUNT(*) as question_count, COALESCE(SUM(user_wrong_question_stats.wrong_count), 0) as wrong_total')
            ->join('questions', 'questions.id', '=', 'user_wrong_question_stats.question_id')
            ->where('user_wrong_question_stats.user_id', $userId)
            ->where('questions.status', 'active')
            ->groupBy('questions.subject_id')
            ->orderByDesc('wrong_total')
            ->first();

        if (! $row) {
            return null;
        }

        $subject = Subject::query()->find($row->subject_id);

        if (! $subject) {
            return null;
        }

        return [
            'subject' => $subject,
            'question_count' => (int) $row->question_count,
            'wrong_total' => (int) $row->wrong_total,
        ];
    }

    private function reportSummary(int $userId): array
    {
        return [
            'pending' => QuestionReport::query()->where('user_id', $userId)->where('status', 'pending')->count(),
            'approved' => QuestionReport::query()->where('user_id', $userId)->where('status', 'approved')->count(),
            'rejected' => QuestionReport::query()->where('user_id', $userId)->where('status', 'rejected')->count(),
        ];
    }

    private function accuracy(int $correct, int $total): ?float
    {
        if ($total <= 0) {
            return null;
        }

        return round(($correct / $total) * 100, 1);
    }
}
