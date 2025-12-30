<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin() || $user->isFaculty()) {
            return $next($request);
        }

        if ($request->routeIs('profile.edit', 'appearance.update')) {
            return $next($request);
        }

        $isComplete = filled($user->student_id) && filled($user->program_code);

        if (! $isComplete) {
            return redirect()
                ->route('profile.edit')
                ->with('warning', 'Please complete your student profile (Student ID and Program Code) to continue.');
        }

        return $next($request);
    }
}
