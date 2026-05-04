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
