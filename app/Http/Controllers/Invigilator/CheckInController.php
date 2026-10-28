<?php

namespace App\Http\Controllers\Invigilator;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\RegistrationLog;
use App\Models\StaffCandidate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckInController extends Controller
{
    private const CHECK_IN_OPENS_HOURS_BEFORE = 2;

    // ---- Screens ----

    public function index(Request $request): View
    {
        $now = now();
        $blocked = $this->candidateExamIds($request->user());

        $sittings = ExamSession::with('exam.category')
            ->where('status', '!=', 'closed')
            ->where('ends_at', '>=', $now->copy()->startOfDay())
            ->where('starts_at', '<=', $now->copy()->addDays(7))
            ->orderBy('starts_at')
            ->get()
            ->reject(fn ($s) => in_array($s->exam_id, $blocked, true));

        $counts = ExamRegistration::whereIn('exam_session_id', $sittings->pluck('id'))
            ->where('status', '!=', 'removed')
            ->select('exam_session_id', 'status', DB::raw('count(*) as c'))
            ->groupBy('exam_session_id', 'status')
            ->get()
            ->groupBy('exam_session_id')
            ->map(fn ($rows) => $rows->pluck('c', 'status'));

        [$today, $upcoming] = $sittings->partition(fn ($s) => $s->starts_at->isToday() || ($s->starts_at->lte($now) && $s->ends_at->gte($now)));

        return view('invigilator.sittings', compact('today', 'upcoming', 'counts'));
    }

    public function show(Request $request, ExamSession $sitting): View
    {
        $this->guard($request, $sitting);
        $sitting->load('exam.category');

        $all = ExamRegistration::where('exam_session_id', $sitting->id)->where('status', '!=', 'removed')->get();
        $cands = $this->candidateMap($all);
        $term = trim((string) $request->query('q'));

        $rows = $all;
        if ($term !== '') {
            $needle = mb_strtolower($term);
            $rows = $all->filter(function ($r) use ($cands, $needle) {
                $c = $cands[$r->candidate_type][$r->candidate_id] ?? null;

                return $c && (
                    str_contains(mb_strtolower((string) ($c->file_number ?? $c->application_number)), $needle)
                    || str_contains(mb_strtolower($c->full_name), $needle)
                );
            });
        }

        // If the person is not in this sitting, say where they are
        $elsewhere = null;
        if ($term !== '' && $rows->isEmpty()) {
            $ids = StaffCandidate::where('file_number', strtoupper($term))->pluck('id');
            $elsewhere = ExamRegistration::with('examSession')
                ->where('exam_id', $sitting->exam_id)->where('candidate_type', 'staff')
                ->whereIn('candidate_id', $ids)->where('status', '!=', 'removed')->first();
        }

        return view('invigilator.sitting', [
            'sitting' => $sitting,
            'rows' => $rows->sortBy(fn ($r) => (int) $r->seat_number)->values(),
            'cands' => $cands,
            'counts' => $all->countBy('status'),
            'lockedCount' => $all->whereNotNull('locked_at')->count(),
            'elsewhere' => $elsewhere,
            'checkInOpensAt' => $sitting->starts_at->copy()->subHours(self::CHECK_IN_OPENS_HOURS_BEFORE),
        ]);
    }

    /** The PIN is shown once, straight after it is issued. */
    public function slip(Request $request, ExamRegistration $registration): View
    {
        $sitting = $this->sittingOf($registration);
        $this->guard($request, $sitting);

        return view('invigilator.slip', [
            'registration' => $registration->load('exam'),
            'sitting' => $sitting,
            'candidate' => $registration->candidate,
            'pin' => session('slip_pin'),
        ]);
    }

    // ---- Actions ----

    public function checkIn(Request $request, ExamRegistration $registration): RedirectResponse
    {
        $sitting = $this->sittingOf($registration);
        $this->guard($request, $sitting);

        $request->validate(
            ['identity_confirmed' => ['accepted']],
            ['identity_confirmed.accepted' => 'Tick the box to confirm you have checked the candidate’s ID.']
        );

        if ($registration->status !== 'assigned') {
            return $this->back($sitting, 'error', 'This candidate cannot be checked in from their current status.');
        }
        if ($sitting->status === 'closed') {
            return $this->back($sitting, 'error', 'This sitting is closed.');
        }

        $opens = $sitting->starts_at->copy()->subHours(self::CHECK_IN_OPENS_HOURS_BEFORE);
        if (now()->lt($opens)) {
            return $this->back($sitting, 'error', 'Check-in opens at '.$opens->format('g:i A, j M').'.');
        }
        if (now()->gt($sitting->ends_at)) {
            return $this->back($sitting, 'error', 'The sitting has ended.');
        }

        $pin = DB::transaction(function () use ($registration, $request) {
            $pin = $registration->issuePin();
            $registration->update([
                'status' => 'checked_in',
                'checked_in_at' => now(),
                'checked_in_by' => $request->user()->id,
                'admitted_at' => null,
            ]);
            $this->log($registration, 'checked_in');

            return $pin;
        });

        return redirect()->route('invigilator.slip', $registration)->with('slip_pin', $pin);
    }

    public function admit(Request $request, ExamRegistration $registration): RedirectResponse
    {
        $sitting = $this->sittingOf($registration);
        $this->guard($request, $sitting);

        if ($registration->status !== 'checked_in') {
            return $this->back($sitting, 'error', 'Only a candidate who has been checked in can be admitted.');
        }

        $registration->update(['status' => 'admitted', 'admitted_at' => now()]);
        $this->log($registration, 'admitted');

        return $this->back($sitting, 'status', $this->label($registration).' admitted. They can now sign in.');
    }

    public function admitAll(Request $request, ExamSession $sitting): RedirectResponse
    {
        $this->guard($request, $sitting);

        $registrations = ExamRegistration::where('exam_session_id', $sitting->id)->where('status', 'checked_in')->get();

        if ($registrations->isEmpty()) {
            return $this->back($sitting, 'error', 'Nobody is waiting to be admitted.');
        }

        DB::transaction(function () use ($registrations, $sitting) {
            foreach ($registrations as $registration) {
                $registration->update(['status' => 'admitted', 'admitted_at' => now()]);
            }
            RegistrationLog::record($sitting->exam_id, 'admitted_all', $registrations->count().' candidate(s) in '.$sitting->name);
        });

        return $this->back($sitting, 'status', $registrations->count().' candidate(s) admitted.');
    }

    public function revoke(Request $request, ExamRegistration $registration): RedirectResponse
    {
        $sitting = $this->sittingOf($registration);
        $this->guard($request, $sitting);

        if ($registration->status !== 'admitted') {
            return $this->back($sitting, 'error', 'This candidate is not admitted.');
        }

        $registration->update(['status' => 'checked_in', 'admitted_at' => null]);
        $this->log($registration, 'admission_revoked');

        return $this->back($sitting, 'status', 'Admission withdrawn. They will be signed out on their next click.');
    }

    public function reissuePin(Request $request, ExamRegistration $registration): RedirectResponse
    {
        $sitting = $this->sittingOf($registration);
        $this->guard($request, $sitting);

        if (! in_array($registration->status, ['checked_in', 'admitted'], true)) {
            return $this->back($sitting, 'error', 'A new PIN can only be issued to a candidate who has been checked in.');
        }

        $pin = $registration->issuePin();
        $this->log($registration, 'pin_reissued');

        return redirect()->route('invigilator.slip', $registration)->with('slip_pin', $pin);
    }

    public function unlock(Request $request, ExamRegistration $registration): RedirectResponse
    {
        $sitting = $this->sittingOf($registration);
        $this->guard($request, $sitting);

        if (! $registration->locked_at) {
            return $this->back($sitting, 'error', 'This account is not locked.');
        }

        $registration->unlock();
        $this->log($registration, 'account_unlocked');

        return $this->back($sitting, 'status', $this->label($registration).' unlocked. They can try their PIN again.');
    }

    public function undoCheckIn(Request $request, ExamRegistration $registration): RedirectResponse
    {
        $sitting = $this->sittingOf($registration);
        $this->guard($request, $sitting);

        if ($registration->status !== 'checked_in') {
            return $this->back($sitting, 'error', 'Only a candidate who is checked in but not yet admitted can be undone.');
        }

        $registration->update([
            'status' => 'assigned', 'checked_in_at' => null, 'checked_in_by' => null,
            'pin_hash' => null, 'pin_issued_at' => null, 'pin_expires_at' => null, 'pin_failed_attempts' => 0, 'locked_at' => null,
        ]);
        $this->log($registration, 'check_in_undone');

        return $this->back($sitting, 'status', 'Check-in undone.');
    }

    public function markAbsent(Request $request, ExamRegistration $registration): RedirectResponse
    {
        $sitting = $this->sittingOf($registration);
        $this->guard($request, $sitting);

        if ($registration->status !== 'assigned') {
            return $this->back($sitting, 'error', 'Only a candidate who has not arrived can be marked absent.');
        }

        $registration->update(['status' => 'absent']);
        $this->log($registration, 'marked_absent');

        return $this->back($sitting, 'status', $this->label($registration).' marked absent.');
    }

    // ---- Helpers ----

    private function sittingOf(ExamRegistration $registration): ExamSession
    {
        $sitting = $registration->examSession;
        abort_if(! $sitting, 404);

        return $sitting;
    }

    /** A staff member who is also a candidate in this exam cannot invigilate it. */
    private function guard(Request $request, ExamSession $sitting): void
    {
        abort_if(
            in_array($sitting->exam_id, $this->candidateExamIds($request->user()), true),
            403,
            'You are a candidate in this exam, so you cannot invigilate it.'
        );
    }

    /** @return int[] ids of exams where this user's file number is on the candidate list */
    private function candidateExamIds(User $user): array
    {
        if (! $user->file_number) {
            return [];
        }

        $ids = StaffCandidate::where('file_number', strtoupper(trim($user->file_number)))->pluck('id');

        return ExamRegistration::where('candidate_type', 'staff')->whereIn('candidate_id', $ids)
            ->where('status', '!=', 'removed')->pluck('exam_id')->all();
    }

    private function candidateMap($registrations): array
    {
        return [
            'staff' => StaffCandidate::whereIn('id', $registrations->where('candidate_type', 'staff')->pluck('candidate_id'))->get()->keyBy('id'),
            'applicant' => Applicant::whereIn('id', $registrations->where('candidate_type', 'applicant')->pluck('candidate_id'))->get()->keyBy('id'),
        ];
    }

    private function label(ExamRegistration $registration): string
    {
        $candidate = $registration->candidate;

        return $candidate->file_number ?? $candidate->application_number ?? ('#'.$registration->id);
    }

    private function log(ExamRegistration $registration, string $action): void
    {
        RegistrationLog::record($registration->exam_id, $action, $this->label($registration), null, $registration->id);
    }

    private function back(ExamSession $sitting, string $key, string $message): RedirectResponse
    {
        return redirect()->route('invigilator.sittings.show', $sitting)->with($key, $message);
    }
}
