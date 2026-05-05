<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->withSession([
            'captcha.prompt' => '3 + 4 = ?',
            'captcha.answer_hash' => Hash::make('7'),
        ])->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'captcha_answer' => '7',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);

        $this->assertAuthenticated();
        $this->assertNotNull(auth()->user()->email_verified_at);
        Notification::assertNothingSent();

        $this->get(RouteServiceProvider::HOME)
            ->assertOk()
            ->assertDontSee('Verify Email');
    }

    public function test_email_verification_setting_controls_new_registration_verification(): void
    {
        Notification::fake();
        app(\App\Services\SettingsService::class)->set('email_verification_required', true);

        $response = $this->withSession([
            'captcha.prompt' => '3 + 4 = ?',
            'captcha.answer_hash' => Hash::make('7'),
        ])->post('/register', [
            'name' => 'Verify User',
            'email' => 'verify@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'captcha_answer' => '7',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertNull(auth()->user()->email_verified_at);
        Notification::assertSentTo(auth()->user(), VerifyEmail::class);

        $this->get(RouteServiceProvider::HOME)
            ->assertRedirect(route('verification.notice'));
    }
}
