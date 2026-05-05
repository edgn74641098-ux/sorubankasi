<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemIsAvailable
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->getBool('maintenance_mode', false)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && ($user->isAdmin() || $user->isEditor())) {
            return $next($request);
        }

        abort(503, 'Sistem bakim modunda. Lutfen daha sonra tekrar deneyin.');
    }
}
