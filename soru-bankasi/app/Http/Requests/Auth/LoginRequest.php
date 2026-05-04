<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SettingsService;
use App\Services\CaptchaService;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'captcha_answer' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        if (! app(CaptchaService::class)->validate($this, $this->input('captcha_answer'))) {
            app(AuditLogService::class)->record(
                null,
                'auth.captcha_failed',
                'auth',
                null,
                null,
                ['email' => $this->input('email')],
                'Giris guvenlik dogrulamasi hatali.',
                $this
            );

            throw ValidationException::withMessages([
                'captcha_answer' => 'Guvenlik dogrulamasi hatali.',
            ]);
        }

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), $this->lockoutSeconds());
            $user = User::query()->where('email', $this->string('email')->lower()->value())->first();

            app(AuditLogService::class)->record(
                $user,
                'auth.login_failed',
                'auth',
                $user?->id,
                null,
                ['email' => $this->input('email')],
                'Basarisiz giris denemesi.',
                $this
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (! Auth::user()->is_active) {
            $blockedUser = Auth::user();
            Auth::logout();
            RateLimiter::hit($this->throttleKey(), $this->lockoutSeconds());

            app(AuditLogService::class)->record(
                $blockedUser,
                'auth.login_blocked_passive',
                'users',
                $blockedUser->id,
                null,
                ['email' => $blockedUser->email],
                'Pasif kullanici girisi engellendi.',
                $this
            );

            throw ValidationException::withMessages([
                'email' => app(SettingsService::class)->getString(
                    'inactive_login_message',
                    'Kullanici hesabiniz pasif duruma getirilmistir. Lutfen yonetici ile iletisime gecin.'
                ),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts())) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    private function maxAttempts(): int
    {
        return app(SettingsService::class)->getInt('login_rate_limit', 5);
    }

    private function lockoutSeconds(): int
    {
        return app(SettingsService::class)->getInt('login_lockout_duration', 900);
    }
}
