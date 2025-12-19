<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentProfileComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin() || $user->isFaculty()) {
            return $next($request);
        }

        if ($user->student_id && $user->course_code) {
            return $next($request);
        }

        if ($request->routeIs('profile.edit') || $request->routeIs('logout')) {
            return $next($request);
        }

        if ($this->isProfileLivewireRequest($request)) {
            return $next($request);
        }

        return redirect()
            ->route('profile.edit')
            ->with('warning', 'Please complete your student profile before continuing.');
    }

    protected function isProfileLivewireRequest(Request $request): bool
    {
        if (! $request->headers->has('X-Livewire')) {
            return false;
        }

        $referer = $request->headers->get('referer');

        if (! is_string($referer) || $referer === '') {
            return false;
        }

        return str_contains($referer, route('profile.edit', absolute: false));
    }
}
