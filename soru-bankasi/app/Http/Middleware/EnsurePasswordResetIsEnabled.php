<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordResetIsEnabled
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->settings->getBool('password_reset_enabled', false), 404);

        return $next($request);
    }
}
