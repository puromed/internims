<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $allowed = in_array($user->role, $roles, true);

        // Let admins through by default if you like:
        if (! $allowed && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            $allowed = true;
        }

        if (! $allowed) {
            abort(403);
        }

        return $next($request);
    }
}
