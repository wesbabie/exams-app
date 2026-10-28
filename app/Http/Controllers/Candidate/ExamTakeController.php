<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CandidateAuth;
use App\Models\Attempt;
use App\Models\AttemptQuestion;
use App\Models\QuestionOption;
use App\Services\AttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamTakeController extends Controller
{
    public function __construct(private AttemptService $attempts)
    {
    }

    /** Shows the section introduction or the current question. The server decides what is current. */
    public function show(Request $request): View|RedirectResponse
    {
        $registration = $request->attributes->get('registration');
        $attempt = $this->attempts->activeAttempt($registration);

        if (! $attempt) {
            return Attempt::where('exam_registration_id', $registration->id)->where('status', 'submitted')->exists()
                ? redirect()->route('candidate.exam.finished')
                : redirect()->route('candidate.dashboard');
        }

        if ($moved = $this->signedInElsewhere($request, $attempt)) {
            return $moved;
        }

        $attempt->update(['last_seen_at' => now()]);

        $aq = $this->attempts->current($attempt);

        if (! $aq) {
            return redirect()->route('candidate.exam.finished');
        }

        $aq->load('paperQuestion.question', 'section');

        $isFirstInSection = ! AttemptQuestion::where('attempt_id', $attempt->id)
            ->where('exam_section_id', $aq->exam_section_id)->where('position', '<', $aq->position)->exists();

        // The first question of each section waits for the candidate to start the section
        if ($isFirstInSection && ! $aq->served_at) {
            return view('candidate.exam.intro', [
                'section' => $aq->section,
                'secondsEach' => $aq->seconds_allowed,
                'count' => AttemptQuestion::where('attempt_id', $attempt->id)->where('exam_section_id', $aq->exam_section_id)->count(),
                'number' => $aq->position,
                'total' => $attempt->questions()->count(),
            ]);
        }

        if (! $aq->served_at) {
            $this->attempts->serve($aq);
            $aq->refresh();
        }

        $question = $aq->paperQuestion->question;
        $options = QuestionOption::whereIn('id', $aq->option_order)->get()->keyBy('id');

        return view('candidate.exam.question', [
            'aq' => $aq,
            'question' => $question,
            'options' => collect($aq->option_order)->map(fn ($id) => $options[$id] ?? null)->filter()->values(),
            'selected' => array_map('intval', $aq->selected_option_ids ?? []),
            'section' => $aq->section,
            'number' => $aq->position,
            'total' => $attempt->questions()->count(),
            'isLast' => $aq->position === $attempt->questions()->count(),
            'remainingMs' => $this->attempts->remainingMs($aq),
            'multi' => $question->type === 'multi_select',
        ]);
    }

    /** The candidate starts a section: the clock on its first question starts now. */
    public function beginSection(Request $request): RedirectResponse
    {
        $attempt = $this->attempts->activeAttempt($request->attributes->get('registration'));

        if ($attempt && ! ($moved = $this->signedInElsewhere($request, $attempt))) {
            $aq = $this->attempts->current($attempt);
            if ($aq && ! $aq->served_at) {
                $this->attempts->serve($aq);
            }
        }

        return $moved ?? redirect()->route('candidate.exam.take');
    }

    /** Autosave. Called every time the candidate changes their selection. */
    public function answer(Request $request): JsonResponse
    {
        $attempt = $this->attempts->activeAttempt($request->attributes->get('registration'));

        if (! $attempt || ! $this->attempts->tokenMatches($attempt, $request->session()->get('attempt_token'))) {
            return response()->json(['reload' => true], 409);
        }

        $data = $request->validate([
            'question' => ['required', 'integer'],
            'selected' => ['nullable', 'array', 'max:6'],
            'selected.*' => ['integer'],
        ]);

        $aq = $attempt->questions()->where('status', 'pending')->first();

        // Wrong question, or the time ran out: the page needs to reload so the server can move on
        if (! $aq || $aq->id !== (int) $data['question'] || ! $this->attempts->isOpenForAnswer($aq)) {
            return response()->json(['reload' => true], 409);
        }

        $aq->update($this->selection($aq, $data['selected'] ?? []));

        return response()->json(['saved' => true, 'remaining_ms' => $this->attempts->remainingMs($aq)]);
    }

    /** Lock the current question and move on. The final selection is sent along as a fallback for a failed autosave. */
    public function next(Request $request): RedirectResponse
    {
        $attempt = $this->attempts->activeAttempt($request->attributes->get('registration'));

        if (! $attempt) {
            return redirect()->route('candidate.exam.take');
        }
        if ($moved = $this->signedInElsewhere($request, $attempt)) {
            return $moved;
        }

        $aq = $attempt->questions()->where('status', 'pending')->first();

        if ($aq && $aq->id === (int) $request->input('question')) {
            if ($this->attempts->isOpenForAnswer($aq)) {
                $aq->update($this->selection($aq, (array) $request->input('selected', [])));
                $this->attempts->close($aq, 'manual');
            } else {
                $this->attempts->close($aq, 'timeout');
            }
        }

        return redirect()->route('candidate.exam.take');
    }

    /** Integrity events from the browser, for example the candidate leaving the tab. */
    public function event(Request $request): JsonResponse
    {
        $attempt = $this->attempts->activeAttempt($request->attributes->get('registration'));
        $type = $request->input('type');

        if ($attempt && in_array($type, ['tab_hidden', 'tab_visible', 'fullscreen_exit'], true)) {
            $this->attempts->event($attempt, $type, $request);
        }

        return response()->json(['ok' => true]);
    }

    public function finished(Request $request): View|RedirectResponse
    {
        $registration = $request->attributes->get('registration');

        if (! Attempt::where('exam_registration_id', $registration->id)->where('status', 'submitted')->exists()) {
            return redirect()->route('candidate.dashboard');
        }

        return view('candidate.exam.finished', ['registration' => $registration->load('exam')]);
    }

    // ---- Helpers ----

    /** Keep only options that belong to this question, and only one for single-answer questions. */
    private function selection(AttemptQuestion $aq, array $ids): array
    {
        $allowed = collect($aq->option_order)->map(fn ($id) => (int) $id);
        $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->filter(fn ($id) => $allowed->contains($id))->values();

        $aq->loadMissing('paperQuestion.question');
        if ($aq->paperQuestion->question->type !== 'multi_select') {
            $ids = $ids->take(1);
        }

        return ['selected_option_ids' => $ids->all(), 'answered_at' => $ids->isEmpty() ? null : now()];
    }

    /** If the candidate signed in again somewhere else, this older session is switched off. */
    private function signedInElsewhere(Request $request, Attempt $attempt): ?RedirectResponse
    {
        if ($this->attempts->tokenMatches($attempt, $request->session()->get('attempt_token'))) {
            return null;
        }

        $request->session()->forget(CandidateAuth::SESSION_KEY);

        return redirect()->route('candidate.login')
            ->withErrors(['exam_number' => 'You signed in on another computer, so this one has been signed out.']);
    }
}
