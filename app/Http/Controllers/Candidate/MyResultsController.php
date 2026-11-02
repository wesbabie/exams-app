<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Mail\ResultsCodeMail;
use App\Models\StaffCandidate;
use App\Models\StaffResultCode;
use App\Support\Audit;
use App\Support\CandidateResults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Staff candidates read their released results here. They have no account and their exam PIN has expired,
 * so a one-time code is emailed to the address on their eligible-list row.
 */
class MyResultsController extends Controller
{
    private const SESSION = 'my_results';
    private const FILE = 'my_results_file';
    private const READ_MINUTES = 30;
    private const CODE_MINUTES = 15;
    private const MAX_WRONG = 5;

    public function create(Request $request): View|RedirectResponse
    {
        return $this->current($request) ? redirect()->route('myresults.show') : view('candidate.results.request');
    }

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
        return $request->session()->has(self::FILE) ? view('candidate.results.verify', ['file' => $request->session()->get(self::FILE)]) : redirect()->route('myresults.request');
    }

    public function verify(Request $request): RedirectResponse
    {
        $file = $request->session()->get(self::FILE);

        if (! $file) {
            return redirect()->route('myresults.request');
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

        if (! $code || $code->attempts >= self::MAX_WRONG) {
            $fail();
        }

        if (! Hash::check($data['code'], $code->code_hash)) {
            $code->increment('attempts');

            if ($code->attempts >= self::MAX_WRONG) {
                Audit::record('candidate.results_code_failed', 'results', 'Results code locked after wrong tries · '.$file, $this->about($candidate));
            }

            $fail();
        }

        $code->update(['used_at' => now()]);

        $request->session()->regenerate();
        $request->session()->forget(self::FILE);
        $request->session()->put(self::SESSION, ['id' => $candidate->id, 'until' => now()->addMinutes(self::READ_MINUTES)->getTimestamp()]);

        return redirect()->route('myresults.show');
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $candidate = $this->current($request);

        if (! $candidate) {
            return redirect()->route('myresults.request')->with('status', 'Your session ended. Ask for a new code to see your results again.');
        }

        $results = CandidateResults::forStaffCandidate($candidate);

        Audit::record('candidate.results_viewed', 'results', 'Viewed their results · '.$candidate->file_number, $this->about($candidate));

        return response()->view('candidate.results.show', ['candidate' => $candidate, 'results' => $results])
            ->header('Cache-Control', 'no-store, private'); // the back button must not bring results back after sign-out
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION);

        return redirect()->route('myresults.request')->with('status', 'You are signed out.');
    }

    private function current(Request $request): ?StaffCandidate
    {
        $held = $request->session()->get(self::SESSION);

        if (! is_array($held) || ($held['until'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget(self::SESSION);

            return null;
        }

        return StaffCandidate::find($held['id']);
    }

    private function issueCode(StaffCandidate $candidate): void
    {
        StaffResultCode::where('staff_candidate_id', $candidate->id)->whereNull('used_at')->delete(); // a new code replaces any older one

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        StaffResultCode::create([
            'staff_candidate_id' => $candidate->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_MINUTES),
        ]);

        Mail::to($candidate->email)->send(new ResultsCodeMail($candidate->first_name, $code, self::CODE_MINUTES));

        Audit::record('candidate.results_code_sent', 'results', 'Results code emailed · '.$candidate->file_number, $this->about($candidate));
    }

    /** @return array<string, mixed> */
    private function about(StaffCandidate $candidate): array
    {
        return ['actor' => null, 'subject_type' => 'staff_candidate', 'subject_id' => $candidate->id, 'subject_label' => $candidate->file_number];
    }
}
