<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternshipExists
{
    /**
     * Handle an incoming request.
     * Ensures user has confirmed their internship placement before accessing logbooks.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Skip for non-students (admin/faculty)
        if ($user->isAdmin() || $user->isFaculty()) {
            return $next($request);
        }

        if (!$user->internships()->exists()) {
            return redirect()->route('placement.index')
                ->with('warning', 'Please confirm your internship placement first.');
        }

        return $next($request);
    }
}
