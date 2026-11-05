<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Mail\ResultsCodeMail;
use App\Models\ExamRegistration;
use App\Models\StaffCandidate;
use App\Models\StaffResultCode;
use App\Support\Audit;
use App\Support\CandidateResults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Candidates read their own released results here, with the number and PIN from their exam-day slip.
 * Staff who lost the PIN can ask for a one-time code by email instead.
 *
 * The PIN is only six digits, so guessing is limited hard and every failure looks the same.
 */
class MyResultsController extends Controller
{
    private const SESSION = 'my_results';
    private const FILE = 'my_results_file';
    private const MAX_WRONG_CODES = 5;

    private const IP_TRIES = 15;          // wrong or unknown tries per computer in ten minutes

    /** A bcrypt hash of nothing in particular, checked when a number is unknown so every answer takes about as long. */
    private const DUMMY_HASH = '$2y$10$xN.0UFFuo9X1uuvh2lvy5uzZny8WsAbRrxoFLEdvKJqwwR/gNywLa';

    public function create(Request $request): View|RedirectResponse
    {
        return $this->held($request) ? redirect()->route('myresults.show') : view('candidate.results.request');
    }

    public function emailForm(Request $request): View|RedirectResponse
    {
        return $this->held($request) ? redirect()->route('myresults.show') : view('candidate.results.email');
    }

    // ---- Number and PIN

    public function pin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'exam_number' => ['required', 'string', 'max:30'],
            'pin' => ['required', 'digits:6'],
        ], ['pin.digits' => 'The PIN is 6 digits.']);

        $number = strtoupper(trim($data['exam_number']));
        $ip = 'myresults-pin-ip:'.$request->ip();
        $perNumber = 'myresults-pin-num:'.$number;
        $perDay = 'myresults-pin-day:'.$number;

        if (RateLimiter::tooManyAttempts($perDay, \App\Support\Settings::int('results.pin_tries_per_day'))) {
            throw ValidationException::withMessages(['pin' => 'Too many tries today. Ask the exam office, or ask for an email code instead.']);
        }

        if (RateLimiter::tooManyAttempts($ip, self::IP_TRIES) || RateLimiter::tooManyAttempts($perNumber, \App\Support\Settings::int('results.pin_tries'))) {
            throw ValidationException::withMessages(['pin' => 'Too many tries. Please wait a while, or ask for an email code instead.']);
        }

        $registrations = ExamRegistration::withPinFor($number);
        $match = $registrations->first(fn (ExamRegistration $r) => Hash::check($data['pin'], $r->pin_hash));

        if ($registrations->isEmpty()) {
            Hash::check($data['pin'], self::DUMMY_HASH); // same effort as a real check
        }

        if (! $match) {
            RateLimiter::hit($ip, 600);
            RateLimiter::hit($perNumber, \App\Support\Settings::int('results.pin_lock_minutes') * 60);
            RateLimiter::hit($perDay, 86400);

            // Only a real number is worth a line in the audit log; junk numbers would flood it.
            if ($registrations->isNotEmpty() && RateLimiter::attempts($perNumber) === \App\Support\Settings::int('results.pin_tries')) {
                Audit::record('candidate.results_pin_locked', 'results', 'Results locked for '.\App\Support\Settings::int('results.pin_lock_minutes').' minutes after wrong PINs · '.$number, [
                    'actor' => null, 'subject_type' => 'registration', 'subject_id' => $registrations->first()->id, 'subject_label' => $number,
                ]);
            }

            throw ValidationException::withMessages(['pin' => 'That number and PIN do not match. Check them against your exam slip.']);
        }

        RateLimiter::clear($perNumber);

        $this->open($request, [$match->id], 'pin');

        return redirect()->route('myresults.show');
    }

    // ---- Lost the PIN: a code by email (staff)

    /** Always answers the same way, so the page never confirms whether a file number or email exists. */
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['file_number' => ['required', 'string', 'max:30']]);
        $file = strtoupper(trim($data['file_number']));

        $ip = 'myresults-send-ip:'.$request->ip();
        $perFile = 'myresults-send-file:'.$file;

        if (RateLimiter::tooManyAttempts($ip, 10) || RateLimiter::tooManyAttempts($perFile, 3)) {
            throw ValidationException::withMessages(['file_number' => 'Too many requests. Please wait a while and try again.']);
        }

        RateLimiter::hit($ip, 600);
        RateLimiter::hit($perFile, 3600);

        $candidate = StaffCandidate::where('file_number', $file)->first();

        if ($candidate && filled($candidate->email) && CandidateResults::forStaffCandidate($candidate)->isNotEmpty()) {
            $this->issueCode($candidate);
        }

        $request->session()->put(self::FILE, $file);

        return redirect()->route('myresults.verify')->with('status', 'If results are available for that file number and an email address is on record, we have sent a 6-digit code to it.');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        return $request->session()->has(self::FILE) ? view('candidate.results.verify', ['file' => $request->session()->get(self::FILE)]) : redirect()->route('myresults.email');
    }

    public function verify(Request $request): RedirectResponse
    {
        $file = $request->session()->get(self::FILE);

        if (! $file) {
            return redirect()->route('myresults.email');
        }

        $data = $request->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'The code is 6 digits.']);

        $ip = 'myresults-verify-ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($ip, 15)) {
            throw ValidationException::withMessages(['code' => 'Too many tries. Please wait a few minutes.']);
        }
        RateLimiter::hit($ip, 600);

        $candidate = StaffCandidate::where('file_number', $file)->first();
        $code = $candidate
            ? StaffResultCode::where('staff_candidate_id', $candidate->id)->whereNull('used_at')->where('expires_at', '>', now())->latest('id')->first()
            : null;

        $fail = fn () => throw ValidationException::withMessages(['code' => 'That code is not right, or it has expired. Ask for a new one.']);

        if (! $code || $code->attempts >= self::MAX_WRONG_CODES) {
            $fail();
        }

        if (! Hash::check($data['code'], $code->code_hash)) {
            $code->increment('attempts');

            if ($code->attempts >= self::MAX_WRONG_CODES) {
                Audit::record('candidate.results_code_failed', 'results', 'Results code locked after wrong tries · '.$file, $this->about($candidate));
            }

            $fail();
        }

        $code->update(['used_at' => now()]);
        $request->session()->forget(self::FILE);

        $ids = \App\Models\ExamRegistration::where('candidate_type', 'staff')->where('candidate_id', $candidate->id)->pluck('id')->all();
        $this->open($request, $ids, 'email');

        return redirect()->route('myresults.show');
    }

    // ---- Reading

    public function show(Request $request): Response|RedirectResponse
    {
        $held = $this->held($request);

        if (! $held) {
            return redirect()->route('myresults.request')->with('status', 'Your session ended. Enter your number and PIN again to see your results.');
        }

        $registrations = ExamRegistration::whereIn('id', $held['ids'])->get();
        $candidate = $registrations->first()?->candidate;
        $results = CandidateResults::forRegistrations($registrations->pluck('id'));

        Audit::record('candidate.results_viewed', 'results', 'Viewed their results · '.($candidate?->file_number ?? $candidate?->application_number), [
            'actor' => null,
            'subject_type' => 'registration',
            'subject_id' => $registrations->first()?->id,
            'subject_label' => $candidate?->file_number ?? $candidate?->application_number,
            'meta' => ['via' => $held['via']],
        ]);

        return response()->view('candidate.results.show', ['candidate' => $candidate, 'results' => $results])
            ->header('Cache-Control', 'no-store, private'); // the back button must not bring results back after sign-out
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION);

        return redirect()->route('myresults.request')->with('status', 'You are signed out.');
    }

    // ---- Helpers

    /** Start a 30-minute reading session for these registrations only. */
    private function open(Request $request, array $registrationIds, string $via): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::SESSION, [
            'ids' => $registrationIds,
            'via' => $via,
            'until' => now()->addMinutes(\App\Support\Settings::int('results.read_minutes'))->getTimestamp(),
        ]);
    }

    /** @return array{ids: array<int, int>, via: string}|null */
    private function held(Request $request): ?array
    {
        $held = $request->session()->get(self::SESSION);

        if (! is_array($held) || ($held['until'] ?? 0) < now()->getTimestamp() || empty($held['ids'])) {
            $request->session()->forget(self::SESSION);

            return null;
        }

        return $held;
    }

    private function issueCode(StaffCandidate $candidate): void
    {
        StaffResultCode::where('staff_candidate_id', $candidate->id)->whereNull('used_at')->delete(); // a new code replaces any older one

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        StaffResultCode::create([
            'staff_candidate_id' => $candidate->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(\App\Support\Settings::int('results.code_minutes')),
        ]);

        Mail::to($candidate->email)->send(new ResultsCodeMail($candidate->first_name, $code, \App\Support\Settings::int('results.code_minutes')));

        Audit::record('candidate.results_code_sent', 'results', 'Results code emailed · '.$candidate->file_number, $this->about($candidate));
    }

    /** @return array<string, mixed> */
    private function about(StaffCandidate $candidate): array
    {
        return ['actor' => null, 'subject_type' => 'staff_candidate', 'subject_id' => $candidate->id, 'subject_label' => $candidate->file_number];
    }
}
