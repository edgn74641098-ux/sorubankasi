<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\AuditLogService;
use App\Services\CaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly CaptchaService $captcha
    )
    {
    }

    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        return view('auth.login', [
            'captcha' => $this->captcha->challenge($request),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $this->auditLog->record(
            $request->user(),
            'auth.login',
            'users',
            $request->user()->id,
            null,
            ['email' => $request->user()->email],
            'Kullanici giris yapti.',
            $request
        );

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->auditLog->record(
            $user,
            'auth.logout',
            'users',
            $user?->id,
            null,
            ['email' => $user?->email],
            'Kullanici cikis yapti.',
            $request
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
