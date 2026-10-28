<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\AttemptEvent;
use App\Models\AttemptQuestion;
use App\Models\ExamRegistration;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Result;
use App\Models\SectionResult;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttemptService
{
    /** Seconds after a deadline in which a late save is still accepted (network lag). */
    public const GRACE_SECONDS = 2;

    public function activeAttempt(ExamRegistration $registration): ?Attempt
    {
        return Attempt::where('exam_registration_id', $registration->id)->where('status', 'in_progress')->latest('id')->first();
    }

    /**
     * Start the exam, or pick up where the candidate left off. Returns a submitted attempt
     * unchanged if the exam is already finished.
     *
     * @throws \DomainException
     */
    public function start(ExamRegistration $registration, Request $request): Attempt
    {
        $existing = Attempt::where('exam_registration_id', $registration->id)->latest('id')->first();
        $token = Str::random(40);

        if ($existing && $existing->status === 'in_progress') {
            $existing->update($this->clientDetails($request, $token));
            $this->event($existing, 'resumed', $request);
            $request->session()->put('attempt_token', $token);

            return $existing;
        }

        if ($existing) {
            return $existing;
        }

        $paper = $registration->examSession->paper;
        if (! $paper || ! $paper->isFrozen()) {
            throw new \DomainException('The paper for your sitting is not ready yet. Please tell the invigilator.');
        }

        $attempt = DB::transaction(function () use ($registration, $paper, $request, $token) {
            $exam = $registration->exam()->with('sections')->first();
            $multiplier = (float) ($registration->extra_time_multiplier ?: 1);

            $attempt = Attempt::create([
                'exam_registration_id' => $registration->id,
                'exam_paper_id' => $paper->id,
                'attempt_no' => 1,
                'status' => 'in_progress',
                'started_at' => now(),
            ] + $this->clientDetails($request, $token));

            $byQuestion = $paper->questions()->with('question.options')->get()->groupBy('exam_section_id');
            $position = 0;

            foreach ($exam->sections as $section) {
                $items = $byQuestion[$section->id] ?? collect();
                $items = $exam->shuffle_questions ? $items->shuffle() : $items->sortBy('position');

                foreach ($items as $paperQuestion) {
                    AttemptQuestion::create([
                        'attempt_id' => $attempt->id,
                        'exam_paper_question_id' => $paperQuestion->id,
                        'exam_section_id' => $section->id,
                        'position' => ++$position,
                        'option_order' => $this->optionOrder($paperQuestion->question, (bool) $exam->shuffle_options),
                        'seconds_allowed' => (int) round($paperQuestion->seconds_allowed * $multiplier),
                        'status' => 'pending',
                    ]);
                }
            }

            $registration->update(['status' => 'in_progress']);
            $this->event($attempt, 'started', $request);

            return $attempt;
        });

        $request->session()->put('attempt_token', $token);

        return $attempt;
    }

    /** Only the most recent sign-in may continue the exam. */
    public function tokenMatches(Attempt $attempt, ?string $token): bool
    {
        return $token && $attempt->session_token_hash && hash_equals($attempt->session_token_hash, hash('sha256', $token));
    }

    /** The question the candidate is on. Closes any that ran out of time, and finishes the exam when none are left. */
    public function current(Attempt $attempt): ?AttemptQuestion
    {
        $attempt->questions()
            ->where('status', 'pending')->whereNotNull('deadline_at')->where('deadline_at', '<=', now())
            ->get()
            ->each(fn ($aq) => $this->close($aq, 'timeout'));

        $next = $attempt->questions()->where('status', 'pending')->first();

        if (! $next) {
            $this->finish($attempt, 'completed');

            return null;
        }

        return $next;
    }

    /** Start the clock on a question. */
    public function serve(AttemptQuestion $aq): void
    {
        $aq->update(['served_at' => now(), 'deadline_at' => now()->addSeconds($aq->seconds_allowed)]);
    }

    public function remainingMs(AttemptQuestion $aq): int
    {
        if (! $aq->deadline_at) {
            return $aq->seconds_allowed * 1000;
        }

        return max(0, (int) ($aq->deadline_at->getPreciseTimestamp(3) - now()->getPreciseTimestamp(3)));
    }

    public function isOpenForAnswer(AttemptQuestion $aq): bool
    {
        return $aq->status === 'pending'
            && $aq->deadline_at
            && now()->lte($aq->deadline_at->copy()->addSeconds(self::GRACE_SECONDS));
    }

    /** Lock a question, score it, and move on. */
    public function close(AttemptQuestion $aq, string $reason): void
    {
        if ($aq->status !== 'pending') {
            return;
        }

        $aq->loadMissing('paperQuestion', 'attempt.registration.exam');
        $exam = $aq->attempt->registration->exam;
        [$isCorrect, $marks] = $this->score($aq, $exam);

        $aq->update([
            'status' => $reason === 'timeout' ? 'timed_out' : (($aq->selected_option_ids ?? []) ? 'answered' : 'unanswered'),
            'is_correct' => $isCorrect,
            'marks_awarded' => $marks,
        ]);

        if ($reason === 'timeout') {
            $this->event($aq->attempt, 'question_timed_out', null, ['position' => $aq->position]);
        }
    }

    public function finish(Attempt $attempt, string $reason): void
    {
        if ($attempt->status !== 'in_progress') {
            return;
        }

        DB::transaction(function () use ($attempt, $reason) {
            foreach ($attempt->questions()->where('status', 'pending')->get() as $aq) {
                $aq->setRelation('attempt', $attempt);
                $this->close($aq, 'manual');
            }

            $attempt->update(['status' => 'submitted', 'submitted_at' => now(), 'submit_reason' => $reason]);
            $attempt->registration->update(['status' => 'completed']);

            $this->computeResult($attempt);
            $this->event($attempt, 'submitted', null, ['reason' => $reason]);
        });
    }

    public function event(Attempt $attempt, string $type, ?Request $request = null, ?array $meta = null): void
    {
        AttemptEvent::create([
            'attempt_id' => $attempt->id,
            'event_type' => $type,
            'meta' => $meta,
            'ip_address' => $request?->ip(),
        ]);
    }

    // ---- Scoring ----

    /**
     * Single choice and true/false: the one correct option. Multi-select: all-or-nothing, so the selection
     * must match the correct options exactly. A wrong answer costs the exam's negative-marking fraction of the mark.
     *
     * @return array{0: ?bool, 1: float}
     */
    private function score(AttemptQuestion $aq, $exam): array
    {
        $selected = collect($aq->selected_option_ids ?? [])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        if (! $selected) {
            return [null, 0.0];
        }

        $correct = QuestionOption::where('question_id', $aq->paperQuestion->question_id)
            ->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        $isCorrect = $selected === $correct;
        $mark = (float) $aq->paperQuestion->marks;
        $marks = $isCorrect ? $mark : -((float) $exam->negative_mark_fraction * $mark);

        return [$isCorrect, round($marks, 2)];
    }

    private function computeResult(Attempt $attempt): void
    {
        $registration = $attempt->registration;
        $exam = $registration->exam()->with('sections')->first();
        $bySection = $attempt->questions()->with('paperQuestion')->get()->groupBy('exam_section_id');

        $total = 0.0;
        $max = 0.0;
        $sections = [];

        foreach ($exam->sections as $section) {
            $rows = $bySection[$section->id] ?? collect();
            $marks = round($rows->sum(fn ($r) => (float) $r->marks_awarded), 2);
            $sectionMax = round($rows->sum(fn ($r) => (float) $r->paperQuestion->marks), 2);

            $sections[] = [
                'exam_section_id' => $section->id,
                'marks' => $marks,
                'max_marks' => $sectionMax,
                'correct_count' => $rows->filter(fn ($r) => $r->is_correct === true)->count(),
                'wrong_count' => $rows->filter(fn ($r) => $r->is_correct === false)->count(),
                'unanswered_count' => $rows->filter(fn ($r) => $r->is_correct === null)->count(),
            ];

            $total += $marks;
            $max += $sectionMax;
        }

        $total = max(0.0, round($total, 2));
        $percentage = $max > 0 ? round($total / $max * 100, 2) : 0.0;
        $passMark = (float) $exam->pass_mark;
        $passed = $exam->pass_mark_type === 'percentage' ? $percentage >= $passMark : $total >= $passMark;

        $result = Result::updateOrCreate(
            ['attempt_id' => $attempt->id],
            [
                'exam_registration_id' => $registration->id,
                'exam_id' => $exam->id,
                'total_marks' => $total,
                'max_marks' => round($max, 2),
                'percentage' => $percentage,
                'passed' => $passed,
            ]
        );

        SectionResult::where('result_id', $result->id)->delete();
        foreach ($sections as $row) {
            SectionResult::create($row + ['result_id' => $result->id]);
        }
    }

    // ---- Helpers ----

    /** The option ids in the order this candidate sees them. "All/None of the above" stay last; true/false stays fixed. */
    private function optionOrder(Question $question, bool $shuffle): array
    {
        $options = $question->options->sortBy('position')->values();

        if (! $shuffle || $question->type === 'true_false') {
            return $options->pluck('id')->all();
        }

        $free = $options->reject(fn ($o) => $o->pin_last)->shuffle()->pluck('id');
        $pinned = $options->filter(fn ($o) => $o->pin_last)->pluck('id');

        return $free->merge($pinned)->values()->all();
    }

    private function clientDetails(Request $request, string $token): array
    {
        return [
            'session_token_hash' => hash('sha256', $token),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'last_seen_at' => now(),
        ];
    }
}
