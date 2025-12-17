<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEligibilityCompleted
{
    /**
     * Handle an incoming request.
     * Ensures all required eligibility documents are approved before proceeding.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Skip for non-students (admin/faculty)
        if ($user->isAdmin() || $user->isFaculty()) {
            return $next($request);
        }

        $requiredDocTypes = ['resume', 'transcript', 'offer_letter'];
        $eligibilityDocs = $user->eligibilityDocs()->get()->keyBy('type');
        
        $allDocsApproved = collect($requiredDocTypes)->every(
            fn($type) => ($eligibilityDocs[$type]->status ?? '') === 'approved'
        );

        if (!$allDocsApproved) {
            return redirect()->route('eligibility.index')
                ->with('warning', 'Please complete your eligibility documents first.');
        }

        return $next($request);
    }
}
