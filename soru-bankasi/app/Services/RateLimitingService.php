<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RateLimitingService
{
    private const DAILY_QUESTION_LIMIT = 20;

    private const LOGIN_ATTEMPT_LIMIT = 5;

    private const LOGIN_LOCKOUT_DURATION_MINUTES = 15;

    private const TEST_CACHE_PREFIX = 'test_limit:';

    private const LOGIN_CACHE_PREFIX = 'login_attempts:';

    private const LOCKOUT_CACHE_PREFIX = 'login_lockout:';

    public function __construct(
        private readonly SettingsService $settingsService
    ) {
    }

    /**
     * Check if user can submit a new question today
     */
    public function canSubmitQuestion(User $user): bool
    {
        $todayCount = $user->submittedQuestions()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return $todayCount < $this->getDailyQuestionLimit();
    }

    /**
     * Get daily question limit
     */
    public function getDailyQuestionLimit(): int
    {
        return (int) $this->settingsService->getString(
            'daily_question_limit',
            (string) self::DAILY_QUESTION_LIMIT
        );
    }

    /**
     * Get remaining questions for today
     */
    public function getRemainingQuestions(User $user): int
    {
        $todayCount = $user->submittedQuestions()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return max(0, $this->getDailyQuestionLimit() - $todayCount);
    }

    /**
     * Check if user is locked out (too many failed login attempts)
     */
    public function isLockedOut(string $email): bool
    {
        return Cache::has($this->lockoutCacheKey($email));
    }

    /**
     * Record failed login attempt
     */
    public function recordFailedLogin(string $email): void
    {
        $key = $this->loginAttemptsCacheKey($email);
        $attempts = (int) Cache::get($key, 0);
        $attempts++;

        if ($attempts >= $this->getLoginAttemptLimit()) {
            // Lock account
            Cache::put(
                $this->lockoutCacheKey($email),
                true,
                now()->addMinutes($this->getLoginLockoutDurationMinutes())
            );

            // Clear attempts cache
            Cache::forget($key);
        } else {
            // Reset TTL (1 hour)
            Cache::put($key, $attempts, now()->addHour());
        }
    }

    /**
     * Reset failed login attempts
     */
    public function resetFailedLogins(string $email): void
    {
        Cache::forget($this->loginAttemptsCacheKey($email));
    }

    /**
     * Get failed login attempts count
     */
    public function getFailedLoginAttempts(string $email): int
    {
        return (int) Cache::get($this->loginAttemptsCacheKey($email), 0);
    }

    /**
     * Get login attempt limit
     */
    public function getLoginAttemptLimit(): int
    {
        return (int) $this->settingsService->getString(
            'login_rate_limit',
            (string) self::LOGIN_ATTEMPT_LIMIT
        );
    }

    /**
     * Get login lockout duration in minutes
     */
    public function getLoginLockoutDurationMinutes(): int
    {
        return (int) ceil($this->settingsService->getInt(
            'login_lockout_duration',
            self::LOGIN_LOCKOUT_DURATION_MINUTES * 60
        ) / 60);
    }

    /**
     * Get lockout remaining time in seconds
     */
    public function getLockoutRemainingSeconds(string $email): int
    {
        $ttl = Cache::store()->connection()->ttl($this->lockoutCacheKey($email));

        return max(0, $ttl);
    }

    /**
     * Check if can start daily tests (daily limit)
     */
    public function canStartTest(User $user, int $maxPerDay = 100): bool
    {
        $key = $this->testLimitCacheKey($user->id);
        $todayCount = (int) Cache::get($key, 0);

        return $todayCount < $maxPerDay;
    }

    /**
     * Record test start
     */
    public function recordTestStart(User $user): void
    {
        $key = $this->testLimitCacheKey($user->id);
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, now()->endOfDay());
    }

    /**
     * Get tests count for today
     */
    public function getTodayTestsCount(User $user): int
    {
        $key = $this->testLimitCacheKey($user->id);

        return (int) Cache::get($key, 0);
    }

    /**
     * Cache keys
     */
    private function loginAttemptsCacheKey(string $email): string
    {
        return self::LOGIN_CACHE_PREFIX . md5(strtolower($email));
    }

    private function lockoutCacheKey(string $email): string
    {
        return self::LOCKOUT_CACHE_PREFIX . md5(strtolower($email));
    }

    private function testLimitCacheKey(int $userId): string
    {
        return self::TEST_CACHE_PREFIX . $userId . ':' . now()->toDateString();
    }
}
