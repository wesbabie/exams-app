<?php

namespace App\Http\Controllers\Intern;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Intake;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('applicant')->check()) {
            return redirect()->route('intern.dashboard');
        }

        return view('intern.register', ['intake' => Intake::currentlyOpen()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $intake = Intake::currentlyOpen();

        if (! $intake) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'Registration is not open right now.']);
        }

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'phone' => PhoneNumber::normalize((string) $request->input('phone')),
        ]);

        $data = $request->validate([
            'surname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'other_names' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:applicants,email'],
            'phone' => ['required', 'regex:'.PhoneNumber::PATTERN, 'unique:applicants,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'email.unique' => 'An application already exists for this email address. Sign in instead.',
            'phone.unique' => 'An application already exists for this phone number. Sign in instead.',
            'phone.regex' => 'Enter a valid phone number, for example 08012345678.',
        ]);

        $applicant = $this->createApplicant($intake, $data);

        Auth::guard('applicant')->login($applicant);
        $request->session()->regenerate();

        $applicant->sendEmailOtp();

        return redirect()->route('intern.verify')->with('status', 'We sent a 6-digit code to '.$applicant->email.'.');
    }

    /** Retry once or twice if two people registered at the same moment and got the same number. */
    private function createApplicant(Intake $intake, array $data): Applicant
    {
        for ($try = 1; ; $try++) {
            try {
                return Applicant::create($data + [
                    'intake_id' => $intake->id,
                    'application_number' => Applicant::nextApplicationNumber($intake),
                    'status' => 'draft',
                ]);
            } catch (UniqueConstraintViolationException $e) {
                if ($try >= 3) {
                    throw $e;
                }
            }
        }
    }
}
