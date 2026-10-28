<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\ExamRegistration;

class CandidateLoginController extends Controller
{
    private const MAX_PIN_ATTEMPTS = 5;   // wrong PINs before the account locks
    private const MAX_REQUESTS = 10;      // login requests per minute, per number and IP

    public function create(): View
    {
        return view('candidate.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'exam_number' => ['required', 'string', 'max:30'],
            'pin' => ['required', 'digits:6'],
        ]);

        // File numbers and application numbers are stored in upper case
        $number = strtoupper(trim($data['exam_number']));
        $throttleKey = 'candidate-login:'.$number.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_REQUESTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'exam_number' => "Too many attempts. Please wait {$seconds} seconds and try again.",
            ]);
        }

        $registration = ExamRegistration::findAdmittedFor($number);

        if ($registration?->locked_at) {
            throw ValidationException::withMessages([
                'exam_number' => 'This account is locked. Please ask an invigilator to unlock it.',
            ]);
        }

        if (! $registration || ! $registration->pinIsValid($data['pin'])) {
            RateLimiter::hit($throttleKey, 60);

            if ($registration && $registration->recordFailedPin(self::MAX_PIN_ATTEMPTS)) {
                throw ValidationException::withMessages([
                    'exam_number' => 'Too many wrong PINs. This account is now locked. Please ask an invigilator to unlock it.',
                ]);
            }

            // One message for every other failure, so the page does not reveal which part was wrong
            throw ValidationException::withMessages([
                'exam_number' => 'We could not sign you in. Check your number and PIN, and make sure an invigilator has admitted you.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $registration->resetPinAttempts();

        Auth::guard('candidate')->login($registration);
        $request->session()->regenerate();

        return redirect()->route('candidate.start');
    }

    public function start(): View
    {
        $registration = Auth::guard('candidate')->user()->load(['candidate', 'exam', 'examSession']);

        return view('candidate.start', compact('registration'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('candidate')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('candidate.login');
    }
}
