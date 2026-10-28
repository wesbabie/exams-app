<?php

namespace App\Http\Middleware;

use App\Models\ExamRegistration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects the candidate pages. The signed-in registration id lives in the session;
 * it is re-checked on every request.
 */
class CandidateAuth
{
    public const SESSION_KEY = 'candidate_registration_id';

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get(self::SESSION_KEY);
        $registration = $id ? ExamRegistration::with('examSession')->find($id) : null;

        if (! $registration || ! $registration->canStillSignIn()) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('candidate.login');
        }

        $request->attributes->set('registration', $registration);

        return $next($request);
    }
}
