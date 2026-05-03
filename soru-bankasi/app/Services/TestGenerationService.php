<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestItem;
use App\Models\User;
use App\Models\UserWrongQuestionStat;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TestGenerationService
{
    public const QUESTION_COUNT = 20;

    public const DURATION_MINUTES = 30;

    public function __construct(
        private readonly SettingsService $settingsService
    ) {
    }

    public function generate(User $user, Subject $subject, string $mode, array $parameters = []): array
    {
        $this->ensureCanStart($user, $subject);

        $selection = match ($mode) {
            'RANDOM' => $this->randomQuestions($subject),
            'DIFFICULTY_RANGE' => $this->difficultyRangeQuestions($subject, $parameters),
            'WEAKNESSES' => $this->weaknessQuestions($user, $subject),
            default => throw $this->httpException(422, 'invalid_mode', 'Geçersiz test modu.'),
        };

        $questions = $selection['questions'];

        if ($questions->count() < self::QUESTION_COUNT) {
            throw $this->httpException(422, 'mode_pool_insufficient', 'Seçilen mod için yeterli soru bulunamadı.');
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

        return [
            'test' => $test,
            'message' => $selection['message'],
        ];
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
            throw $this->httpException(422, 'insufficient_questions', 'Bu derste test başlatmak için en az 20 aktif soru olmalıdır.');
        }

        $hasActiveTest = Test::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveTest) {
            throw $this->httpException(409, 'active_test_exists', 'Devam eden bir testiniz zaten var.');
        }
    }

    private function randomQuestions(Subject $subject): array
    {
        return [
            'questions' => $this->baseSubjectQuestionQuery($subject)
                ->inRandomOrder()
                ->limit(self::QUESTION_COUNT)
                ->get(),
            'message' => null,
        ];
    }

    private function difficultyRangeQuestions(Subject $subject, array $parameters): array
    {
        $min = max(1, (int) ($parameters['min_difficulty'] ?? 1));
        $max = min(10, (int) ($parameters['max_difficulty'] ?? 10));

        if ($min > $max) {
            throw $this->httpException(422, 'invalid_difficulty_range', 'Zorluk aralığı geçersiz.');
        }

        $questions = $this->baseSubjectQuestionQuery($subject)
            ->whereBetween('difficulty_score', [$min, $max])
            ->inRandomOrder()
            ->limit(self::QUESTION_COUNT)
            ->get();

        if ($questions->count() >= self::QUESTION_COUNT) {
            return [
                'questions' => $questions,
                'message' => null,
            ];
        }

        $missing = self::QUESTION_COUNT - $questions->count();

        $additional = $this->baseSubjectQuestionQuery($subject)
            ->whereNotIn('id', $questions->pluck('id'))
            ->inRandomOrder()
            ->limit($missing)
            ->get();

        return [
            'questions' => $questions->concat($additional)->values(),
            'message' => 'Zorluk aralığındaki sorular yetmediği için test ders havuzundan tamamlandı.',
        ];
    }

    private function weaknessQuestions(User $user, Subject $subject): array
    {
        $cooldowns = [72, 48, 24, 0];
        $selected = collect();
        $usedCooldown = 72;

        foreach ($cooldowns as $cooldown) {
            $threshold = Carbon::now()->subHours($cooldown);

            $selected = $this->baseSubjectQuestionQuery($subject)
                ->select('questions.*')
                ->join('user_wrong_question_stats', 'questions.id', '=', 'user_wrong_question_stats.question_id')
                ->where('user_wrong_question_stats.user_id', $user->id)
                ->where('user_wrong_question_stats.last_wrong_at', '<=', $threshold)
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
            $additional = $this->baseSubjectQuestionQuery($subject)
                ->whereNotIn('id', $wrongQuestionIds)
                ->inRandomOrder()
                ->limit(self::QUESTION_COUNT - $selected->count())
                ->get();

            $selected = $selected->concat($additional)->values();
        }

        if ($selected->count() < self::QUESTION_COUNT) {
            throw $this->httpException(422, 'mode_pool_insufficient', 'Takıldıklarım havuzunda yeterli soru bulunamadı.');
        }

        $messageParts = [];

        if ($usedCooldown !== 72) {
            $messageParts[] = "Takıldıklarım havuzu {$usedCooldown} saat bekleme kuralına düşürülerek tarandı.";
        }

        if ($wrongQuestionIds->count() < self::QUESTION_COUNT) {
            $messageParts[] = 'Takıldıklarım havuzu rastgele sorularla tamamlandı.';
        }

        return [
            'questions' => $selected,
            'message' => empty($messageParts) ? null : implode(' ', $messageParts),
        ];
    }

    private function baseSubjectQuestionQuery(Subject $subject)
    {
        return Question::query()
            ->where('subject_id', $subject->id)
            ->where('status', 'active');
    }

    private function httpException(int $status, string $error, string $message): HttpException
    {
        return new HttpException($status, json_encode([
            'error' => $error,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE));
    }
}
