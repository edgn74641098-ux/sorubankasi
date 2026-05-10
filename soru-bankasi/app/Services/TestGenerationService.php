<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestItem;
use App\Models\User;
use App\Models\UserRecentQuestionHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TestGenerationService
{
    public const QUESTION_COUNT = 20;
    public const DURATION_MINUTES = 30;
    private const MASTERED_MIN_ATTEMPTS = 5;
    private const MASTERED_MIN_ACCURACY = 0.8;
    private const MASTERED_MAX_RATIO_IN_TEST = 0.2;

    public function __construct(
        private readonly SettingsService $settingsService
    ) {
    }

    public function generate(User $user, Subject $subject, string $mode, array $parameters = []): array
    {
        $excludeSolvedQuestions = (bool) ($parameters['exclude_solved_questions'] ?? false);
        $this->ensureCanStart($user, $subject);

        $selection = match ($mode) {
            'RANDOM' => $this->randomQuestions($user, $subject, $excludeSolvedQuestions),
            'DIFFICULTY_RANGE' => $this->difficultyRangeQuestions($user, $subject, $parameters, $excludeSolvedQuestions),
            'WEAKNESSES' => $this->weaknessQuestions($user, $subject, $excludeSolvedQuestions),
            default => throw $this->httpException(422, 'invalid_mode', 'Gecersiz test modu.'),
        };

        $questions = $selection['questions'];
        if ($questions->count() < self::QUESTION_COUNT) {
            throw $this->httpException(422, 'mode_pool_insufficient', 'Secilen mod icin yeterli soru bulunamadi.');
        }

        $feedbackMode = $this->settingsService->getString('test_feedback_mode', 'DELAYED_FEEDBACK');
        $test = DB::transaction(function () use ($user, $subject, $questions, $feedbackMode) {
            $test = Test::query()->create([
                'user_id' => $user->id,
                'subject_id' => $subject->id,
                'question_count' => self::QUESTION_COUNT,
                'duration_minutes' => self::DURATION_MINUTES,
                'started_at' => now(),
                'status' => 'active',
                'feedback_mode' => $feedbackMode,
                'aborted' => false,
            ]);

            foreach ($questions as $question) {
                TestItem::query()->create([
                    'test_id' => $test->id,
                    'question_id' => $question->id,
                ]);
            }

            return $test->load(['subject', 'items.question']);
        });

        $dailyLimit = $this->settingsService->getInt('daily_test_limit', 20);
        $todayTestCount = Test::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $message = $selection['message'];
        if ($todayTestCount >= $dailyLimit) {
            $message = 'Bugun son test hakkinizi kullandiniz.' . ($message ? ' ' . $message : '');
        } elseif ($todayTestCount >= max(10, (int) floor($dailyLimit / 2))) {
            $message = 'Bugun cok sayida test cozdunuz.' . ($message ? ' ' . $message : '');
        }

        return ['test' => $test, 'message' => $message];
    }

    private function ensureCanStart(User $user, Subject $subject): void
    {
        if ($this->settingsService->getBool('maintenance_mode', false)) {
            throw $this->httpException(503, 'maintenance_mode', 'Sistem bakim modunda. Lutfen daha sonra tekrar deneyin.');
        }

        $dailyLimit = $this->settingsService->getInt('daily_test_limit', 20);
        $todayFinishedOrActive = Test::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayFinishedOrActive >= $dailyLimit) {
            throw $this->httpException(429, 'daily_test_limit_reached', 'Bugun maksimum test limitine ulastiniz.');
        }

        $activeCount = Question::query()
            ->where('subject_id', $subject->id)
            ->where('status', 'active')
            ->count();

        if ($activeCount < self::QUESTION_COUNT) {
            throw $this->httpException(422, 'insufficient_questions', 'Bu derste test baslatmak icin en az 20 aktif soru olmalidir.');
        }

        $hasActiveTest = Test::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveTest) {
            throw $this->httpException(409, 'active_test_exists', 'Devam eden bir testiniz zaten var.');
        }
    }

    private function randomQuestions(User $user, Subject $subject, bool $excludeSolvedQuestions): array
    {
        return [
            'questions' => $this->balancedQuestionSelection($user, $subject, null, self::QUESTION_COUNT, $excludeSolvedQuestions),
            'message' => null,
        ];
    }

    private function difficultyRangeQuestions(User $user, Subject $subject, array $parameters, bool $excludeSolvedQuestions): array
    {
        $min = max(1, (int) ($parameters['min_difficulty'] ?? 1));
        $max = min(10, (int) ($parameters['max_difficulty'] ?? 10));
        if ($min > $max) {
            throw $this->httpException(422, 'invalid_difficulty_range', 'Zorluk araligi gecersiz.');
        }

        $questions = $this->balancedQuestionSelection(
            $user,
            $subject,
            fn ($query) => $query->whereBetween('difficulty_score', [$min, $max]),
            self::QUESTION_COUNT,
            $excludeSolvedQuestions
        );

        if ($questions->count() >= self::QUESTION_COUNT) {
            return ['questions' => $questions, 'message' => null];
        }

        $missing = self::QUESTION_COUNT - $questions->count();
        $additional = $this->balancedQuestionSelection(
            $user,
            $subject,
            fn ($query) => $query->whereNotIn('id', $questions->pluck('id')),
            $missing,
            $excludeSolvedQuestions
        );

        return [
            'questions' => $questions->concat($additional)->values(),
            'message' => 'Zorluk araligindaki sorular yetmedigi icin test ders havuzundan tamamlandi.',
        ];
    }

    private function balancedQuestionSelection(
        User $user,
        Subject $subject,
        ?callable $scope = null,
        int $limit = self::QUESTION_COUNT,
        bool $excludeSolvedQuestions = false
    ) {
        $masteredQuestionIds = $this->masteredQuestionIds($user, $subject);
        $maxMasteredQuestions = (int) floor($limit * self::MASTERED_MAX_RATIO_IN_TEST);
        $solvedQuestionIds = $excludeSolvedQuestions ? $this->solvedQuestionIds($user, $subject) : collect();

        $buildQuery = function () use ($subject, $scope, $solvedQuestionIds) {
            $query = $this->baseSubjectQuestionQuery($subject, $solvedQuestionIds);
            if ($scope !== null) {
                $scope($query);
            }
            return $query;
        };

        $regularQuestions = $buildQuery()
            ->when($masteredQuestionIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $masteredQuestionIds))
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        if ($regularQuestions->count() >= $limit) {
            return $regularQuestions;
        }

        $selected = $regularQuestions;
        $remaining = $limit - $selected->count();
        $masteredLimit = max(0, min($maxMasteredQuestions, $remaining));

        if ($masteredLimit > 0 && $masteredQuestionIds->isNotEmpty()) {
            $masteredQuestions = $buildQuery()
                ->whereIn('id', $masteredQuestionIds)
                ->whereNotIn('id', $selected->pluck('id'))
                ->inRandomOrder()
                ->limit($masteredLimit)
                ->get();
            $selected = $selected->concat($masteredQuestions)->unique('id')->values();
        }

        if ($selected->count() >= $limit) {
            return $selected;
        }

        return $selected
            ->concat(
                $buildQuery()
                    ->whereNotIn('id', $selected->pluck('id'))
                    ->inRandomOrder()
                    ->limit($limit - $selected->count())
                    ->get()
            )
            ->unique('id')
            ->values();
    }

    private function masteredQuestionIds(User $user, Subject $subject)
    {
        return UserRecentQuestionHistory::query()
            ->where('user_id', $user->id)
            ->whereIn('question_id', $this->baseSubjectQuestionQuery($subject)->select('id'))
            ->whereRaw('(correct_count + wrong_count) >= ?', [self::MASTERED_MIN_ATTEMPTS])
            ->whereRaw('correct_count >= ((correct_count + wrong_count) * ?)', [self::MASTERED_MIN_ACCURACY])
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    private function weaknessQuestions(User $user, Subject $subject, bool $excludeSolvedQuestions): array
    {
        $cooldowns = [72, 48, 24, 0];
        $selected = collect();
        $usedCooldown = 72;
        $solvedQuestionIds = $excludeSolvedQuestions ? $this->solvedQuestionIds($user, $subject)->toArray() : [];

        foreach ($cooldowns as $cooldown) {
            $exclusionThreshold = Carbon::now()->subHours($cooldown);
            $recentlyAnsweredIds = UserRecentQuestionHistory::query()
                ->where('user_id', $user->id)
                ->where('last_answered_at', '>=', $exclusionThreshold)
                ->pluck('question_id')
                ->toArray();

            $selected = $this->baseSubjectQuestionQuery($subject)
                ->select('questions.*')
                ->join('user_wrong_question_stats', 'questions.id', '=', 'user_wrong_question_stats.question_id')
                ->where('user_wrong_question_stats.user_id', $user->id)
                ->whereNotIn('questions.id', $recentlyAnsweredIds)
                ->when(!empty($solvedQuestionIds), fn ($query) => $query->whereNotIn('questions.id', $solvedQuestionIds))
                ->orderByDesc('user_wrong_question_stats.wrong_count')
                ->orderByDesc('user_wrong_question_stats.last_wrong_at')
                ->limit(self::QUESTION_COUNT)
                ->get();

            $usedCooldown = $cooldown;
            if ($selected->count() >= self::QUESTION_COUNT) {
                break;
            }
        }

        $wrongQuestionIds = $selected->pluck('id');
        if ($selected->count() < self::QUESTION_COUNT) {
            $additional = $this->balancedQuestionSelection(
                $user,
                $subject,
                fn ($query) => $query->whereNotIn('id', $wrongQuestionIds),
                self::QUESTION_COUNT - $selected->count(),
                $excludeSolvedQuestions
            );
            $selected = $selected->concat($additional)->values();
        }

        if ($selected->count() < self::QUESTION_COUNT) {
            throw $this->httpException(422, 'mode_pool_insufficient', 'Takildiklarim havuzunda yeterli soru bulunamadi.');
        }

        $messageParts = [];
        if ($usedCooldown !== 72) {
            $messageParts[] = "Takildiklarim havuzu {$usedCooldown} saat bekleme kuralina dusurulerek tarandi.";
        }
        if ($wrongQuestionIds->count() < self::QUESTION_COUNT) {
            $messageParts[] = 'Takildiklarim havuzu rastgele sorularla tamamlandi.';
        }

        return [
            'questions' => $selected,
            'message' => empty($messageParts) ? null : implode(' ', $messageParts),
        ];
    }

    private function baseSubjectQuestionQuery(Subject $subject, $excludeIds = null)
    {
        return Question::query()
            ->where('subject_id', $subject->id)
            ->when($excludeIds !== null && count($excludeIds) > 0, fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->where('status', 'active');
    }

    private function solvedQuestionIds(User $user, Subject $subject)
    {
        return UserRecentQuestionHistory::query()
            ->where('user_id', $user->id)
            ->whereRaw('(correct_count + wrong_count) > 0')
            ->whereIn('question_id', $this->baseSubjectQuestionQuery($subject)->select('id'))
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    private function httpException(int $status, string $error, string $message): HttpException
    {
        return new HttpException($status, json_encode([
            'error' => $error,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE));
    }
}
