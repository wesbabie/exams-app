<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Services\AttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateDashboardController extends Controller
{
    public function show(Request $request, AttemptService $attempts): View|RedirectResponse
    {
        $registration = $request->attributes->get('registration');
        $registration->load(['exam.sections', 'examSession.paper']);

        if (Attempt::where('exam_registration_id', $registration->id)->where('status', 'submitted')->exists()) {
            return redirect()->route('candidate.exam.finished');
        }

        $exam = $registration->exam;
        $sections = $exam->sections;

        // Candidates with an extra-time multiplier get longer per question
        $multiplier = (float) ($registration->extra_time_multiplier ?: 1);

        $totals = [
            'questions' => (int) $sections->sum('question_count'),
            'marks' => (float) $sections->sum(fn ($s) => $s->question_count * $s->marks_per_question),
            'seconds' => (int) round($sections->sum(fn ($s) => $s->question_count * $s->seconds_per_question) * $multiplier),
        ];

        return view('candidate.dashboard', [
            'registration' => $registration,
            'exam' => $exam,
            'sections' => $sections,
            'totals' => $totals,
            'multiplier' => $multiplier,
            'attempt' => $attempts->activeAttempt($registration),
            'paperReady' => (bool) $registration->examSession->paper?->isFrozen(),
        ]);
    }

    /** Start the exam, or carry on with one that is already under way. */
    public function begin(Request $request, AttemptService $attempts): RedirectResponse
    {
        $registration = $request->attributes->get('registration');

        if (! $attempts->activeAttempt($registration)) {
            $request->validate(['agree' => ['accepted']], ['agree.accepted' => 'Tick the box to confirm you have read the rules.']);
        }

        try {
            $attempt = $attempts->start($registration, $request);
        } catch (\DomainException $e) {
            return redirect()->route('candidate.dashboard')->with('notice', $e->getMessage());
        }

        return $attempt->status === 'submitted'
            ? redirect()->route('candidate.exam.finished')
            : redirect()->route('candidate.exam.take');
    }
}
