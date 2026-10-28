<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applicants must confirm their email address before using the rest of the portal.
 * Use after the "auth:applicant" middleware.
 */
class EnsureApplicantVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $applicant = $request->user('applicant');

        if ($applicant && ! $applicant->hasVerifiedEmail()) {
            return redirect()->route('intern.verify');
        }

        return $next($request);
    }
}
