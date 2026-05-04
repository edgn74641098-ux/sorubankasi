<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function redirect(): RedirectResponse
    {
        if (! $this->settings->getBool('google_auth_enabled', true)) {
            return redirect()
                ->route('login')
                ->with('status', 'Google ile giris su anda kapali.');
        }

        if (! $this->googleConfigured()) {
            return redirect()
                ->route('login')
                ->with('status', 'Google ile giris icin GOOGLE_CLIENT_ID ve GOOGLE_CLIENT_SECRET ayarlanmalidir.');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless($this->settings->getBool('google_auth_enabled', true), 403);

        if (! $this->googleConfigured()) {
            return redirect()
                ->route('login')
                ->with('status', 'Google ile giris henuz yapilandirilmadi.');
        }

        $googleUser = Socialite::driver('google')->user();
        $email = strtolower((string) $googleUser->getEmail());

        abort_if($email === '', 422, 'Google hesabindan e-posta alinamadi.');

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        } else {
            abort_unless($this->settings->getBool('registration_open', true), 403);

            $user = User::query()->create([
                'role_id' => Role::query()->firstOrCreate(['name' => 'user'])->id,
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'password' => Hash::make(Str::random(48)),
                'email_verified_at' => now(),
                'total_score' => 0,
            ]);
        }

        abort_if(! $user->is_active, 403, $this->settings->getString(
            'inactive_login_message',
            'Kullanici hesabiniz pasif duruma getirilmistir. Lutfen yonetici ile iletisime gecin.'
        ));

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function googleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
