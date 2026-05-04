<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\AuditLogService;
use App\Services\CaptchaService;
use App\Services\SettingsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly CaptchaService $captcha,
        private readonly AuditLogService $auditLog
    )
    {
    }

    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        abort_unless($this->settings->getBool('registration_open', true), 403);

        return view('auth.register', [
            'captcha' => $this->captcha->challenge($request),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->settings->getBool('registration_open', true), 403);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'captcha_answer' => ['required', 'string'],
        ]);

        if (! $this->captcha->validate($request, $request->input('captcha_answer'))) {
            $this->auditLog->record(
                null,
                'auth.registration_captcha_failed',
                'auth',
                null,
                null,
                ['email' => $request->input('email')],
                'Kayit guvenlik dogrulamasi hatali.',
                $request
            );

            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'captcha_answer']))
                ->withErrors(['captcha_answer' => 'Guvenlik dogrulamasi hatali.']);
        }

        $user = User::create([
            'role_id' => Role::query()->firstOrCreate(['name' => 'user'])->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'total_score' => 0,
        ]);

        event(new Registered($user));

        $this->auditLog->record(
            $user,
            'auth.registered',
            'users',
            $user->id,
            null,
            ['name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id],
            'Yeni kullanici kaydi olusturuldu.',
            $request
        );

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
