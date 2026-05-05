<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->withValidCaptcha()->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha_answer' => '7',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'auth.login',
            'entity_type' => 'users',
            'entity_id' => $user->id,
        ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->withValidCaptcha()->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'captcha_answer' => '7',
        ]);

        $this->assertGuest();
    }

    public function test_passive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->withValidCaptcha()->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha_answer' => '7',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Kullanici hesabiniz pasif duruma getirilmistir. Lutfen yonetici ile iletisime gecin.',
        ]);
    }

    public function test_inactive_login_message_and_login_rate_limit_follow_settings(): void
    {
        app(\App\Services\SettingsService::class)->set('inactive_login_message', 'Hesabiniz yonetici tarafindan gecici olarak pasife alindi.');
        app(\App\Services\SettingsService::class)->set('login_rate_limit', 2);
        app(\App\Services\SettingsService::class)->set('login_lockout_duration', 60);

        $passiveUser = User::factory()->create([
            'is_active' => false,
        ]);

        $this->withValidCaptcha()
            ->from('/login')
            ->post('/login', [
                'email' => $passiveUser->email,
                'password' => 'password',
                'captcha_answer' => '7',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'Hesabiniz yonetici tarafindan gecici olarak pasife alindi.',
            ]);

        $activeUser = User::factory()->create();

        foreach (range(1, 2) as $attempt) {
            $this->withValidCaptcha()->post('/login', [
                'email' => $activeUser->email,
                'password' => 'wrong-password',
                'captcha_answer' => '7',
            ]);
        }

        $this->withValidCaptcha()
            ->post('/login', [
                'email' => $activeUser->email,
                'password' => 'wrong-password',
                'captcha_answer' => '7',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_google_login_visibility_follows_setting(): void
    {
        app(\App\Services\SettingsService::class)->set('google_auth_enabled', false);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Google ile devam et');

        $this->get(route('auth.google.redirect'))
            ->assertForbidden();

        app(\App\Services\SettingsService::class)->set('google_auth_enabled', true);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Google ile devam et');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    private function withValidCaptcha(): self
    {
        return $this->withSession([
            'captcha.prompt' => '3 + 4 = ?',
            'captcha.answer_hash' => Hash::make('7'),
        ]);
    }
}
