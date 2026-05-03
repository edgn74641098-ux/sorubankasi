<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || empty($roles)) {
            abort(403);
        }

        $roleName = $user->role?->name;

        if (! in_array($roleName, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}