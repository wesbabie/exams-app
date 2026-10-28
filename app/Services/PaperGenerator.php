<?php

namespace App\Services;

use App\Models\ExamPaper;
use App\Models\ExamPaperQuestion;
use App\Models\ExamSection;
use App\Models\ExamSession;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaperGenerator
{
    /**
     * Build (or rebuild) the paper for a sitting. Fixed-set exams copy the chosen questions.
     * Pool-draw exams draw the same number of questions from each topic, preferring questions
     * that earlier sittings of the same exam did not use.
     *
     * @throws \DomainException when the exam is not ready or the paper is already frozen
     */
    public function generate(ExamSession $session, ?int $userId = null): ExamPaper
    {
        $exam = $session->exam()->with('sections')->first();

        if (! in_array($exam->status, ['approved', 'scheduled'], true)) {
            throw new \DomainException('The exam must be approved before papers can be generated.');
        }
        if ($issues = $exam->readinessIssues()) {
            throw new \DomainException('The exam is not ready: '.implode(' ', $issues));
        }

        $existing = $session->paper;
        if ($existing?->isFrozen()) {
            throw new \DomainException('This paper is frozen and cannot be regenerated.');
        }

        return DB::transaction(function () use ($session, $exam, $userId, $existing) {
            $seed = random_int(1, 2000000000);

            $paper = $existing ?? new ExamPaper(['exam_session_id' => $session->id]);
            $paper->fill(['seed' => $seed, 'generated_by' => $userId, 'generated_at' => now(), 'frozen_at' => null])->save();
            $paper->questions()->delete();

            $otherPapers = ExamPaper::whereIn('exam_session_id', $exam->sessions()->where('id', '!=', $session->id)->pluck('id'))->pluck('id');
            $usedElsewhere = ExamPaperQuestion::whereIn('exam_paper_id', $otherPapers)->pluck('question_id')->all();
            $inThisPaper = [];

            foreach ($exam->sections as $section) {
                $questions = $exam->paper_mode === 'fixed_set'
                    ? $this->fixed($section)
                    : $this->drawn($section, $seed, $usedElsewhere, $inThisPaper);

                foreach ($questions->values() as $i => $question) {
                    ExamPaperQuestion::create([
                        'exam_paper_id' => $paper->id,
                        'exam_section_id' => $section->id,
                        'question_id' => $question->id,
                        'position' => $i + 1,
                        // Snapshotted so later changes to the exam never alter a paper
                        'marks' => $section->marks_per_question,
                        'seconds_allowed' => $question->time_seconds ?: $section->seconds_per_question,
                    ]);
                    $inThisPaper[] = $question->id;
                }
            }

            return $paper;
        });
    }

    public function freeze(ExamPaper $paper): void
    {
        $paper->update(['frozen_at' => now()]);

        $exam = $paper->session->exam;
        if ($exam->status === 'approved') {
            $exam->update(['status' => 'scheduled']);
        }
    }

    private function fixed(ExamSection $section): Collection
    {
        return $section->fixedQuestions()->with('question')->get()->map(fn ($row) => $row->question)->filter();
    }

    private function drawn(ExamSection $section, int $seed, array $usedElsewhere, array $inThisPaper): Collection
    {
        $picked = collect();

        foreach ($section->blueprints()->orderBy('topic_id')->get() as $blueprint) {
            $pool = Question::where('status', 'approved')
                ->where('topic_id', $blueprint->topic_id)
                ->whereNotIn('id', $inThisPaper)
                ->get();

            $fresh = $pool->reject(fn ($q) => in_array($q->id, $usedElsewhere, true));
            $take = $fresh->shuffle($seed + $blueprint->topic_id)->take($blueprint->question_count);

            if ($take->count() < $blueprint->question_count) {
                $rest = $pool->reject(fn ($q) => $take->contains('id', $q->id))
                    ->shuffle($seed + $blueprint->topic_id + 1)
                    ->take($blueprint->question_count - $take->count());
                $take = $take->merge($rest);
            }

            $picked = $picked->merge($take);
        }

        return $picked->shuffle($seed + $section->id);
    }
}
