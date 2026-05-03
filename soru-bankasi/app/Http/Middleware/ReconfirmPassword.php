<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ReconfirmPassword
{
    private const SESSION_KEY = 'auth.reconfirmed_at';

    private const TTL_SECONDS = 600;

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isConfirmed($request)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $password = (string) $request->input('current_password', $request->input('password', ''));

        if ($password !== '' && Auth::guard('web')->validate([
            'email' => $user->email,
            'password' => $password,
        ])) {
            $request->session()->put(self::SESSION_KEY, time());

            return $next($request);
        }

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => __('auth.password'),
            ], 423);
        }

        throw ValidationException::withMessages([
            'current_password' => __('auth.password'),
        ]);
    }

    private function isConfirmed(Request $request): bool
    {
        $confirmedAt = (int) $request->session()->get(self::SESSION_KEY, 0);

        return $confirmedAt !== 0 && (time() - $confirmedAt) < self::TTL_SECONDS;
    }
}