<?php

namespace App\Http\Controllers\Intern;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VerifyEmailController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $applicant = $request->user('applicant');

        if ($applicant->hasVerifiedEmail()) {
            return redirect()->route('intern.dashboard');
        }

        return view('intern.verify', ['applicant' => $applicant]);
    }

    public function store(Request $request): RedirectResponse
    {
        $applicant = $request->user('applicant');

        if ($applicant->hasVerifiedEmail()) {
            return redirect()->route('intern.dashboard');
        }

        $data = $request->validate(['code' => ['required', 'digits:6']], [
            'code.digits' => 'The code is 6 digits.',
        ]);

        $message = match ($applicant->verifyEmailOtp($data['code'])) {
            'ok' => null,
            'wrong' => 'That code is not correct.',
            'locked' => 'Too many wrong codes. Request a new code.',
            'expired' => 'That code has expired. Request a new code.',
        };

        if ($message) {
            throw ValidationException::withMessages(['code' => $message]);
        }

        return redirect()->route('intern.dashboard')->with('status', 'Your email address is verified. Complete your application below.');
    }

    public function resend(Request $request): RedirectResponse
    {
        /** @var Applicant $applicant */
        $applicant = $request->user('applicant');

        if ($applicant->hasVerifiedEmail()) {
            return redirect()->route('intern.dashboard');
        }

        if (! $applicant->canResendEmailOtp()) {
            return back()->with('error', 'Please wait '.$applicant->resendWaitSeconds().' seconds before asking for another code.');
        }

        $applicant->sendEmailOtp();

        return back()->with('status', 'A new code was sent to '.$applicant->email.'.');
    }
}
