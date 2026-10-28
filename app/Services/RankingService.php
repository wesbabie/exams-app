<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Result;
use Illuminate\Support\Collection;

class RankingService
{
    /**
     * Results for an exam, best first, with ranks. Candidates who tie on every rule share a rank (1, 2, 2, 4).
     * The tie-break is the one chosen on the exam: the field section's marks, or the earliest submission.
     *
     * @return Collection<int, array{result: Result, rank: int, shortlisted: bool}>
     */
    public function ranked(Exam $exam): Collection
    {
        $exam->loadMissing('sections');
        $tieSection = $this->tieBreakSection($exam);

        $results = Result::where('exam_id', $exam->id)
            ->with(['attempt.registration.examSession', 'sections'])
            ->get()
            ->sort(fn ($a, $b) => $this->compare($a, $b, $exam, $tieSection))
            ->values();

        $rows = collect();
        $previous = null;
        $previousRank = 0;

        foreach ($results as $i => $result) {
            $rank = ($previous && $this->compare($previous, $result, $exam, $tieSection) === 0) ? $previousRank : $i + 1;

            $rows->push([
                'result' => $result,
                'rank' => $rank,
                // The shortlist is the top N among those who passed. Candidates tied at the cut-off are all included.
                'shortlisted' => (bool) ($result->passed && $exam->top_n && $rank <= $exam->top_n),
            ]);

            $previous = $result;
            $previousRank = $rank;
        }

        return $rows;
    }

    /** Save the ranks and the shortlist on the results. */
    public function persist(Exam $exam): void
    {
        foreach ($this->ranked($exam) as $row) {
            $row['result']->update(['rank_position' => $row['rank'], 'shortlisted' => $row['shortlisted']]);
        }
    }

    /** The section used for the "field section" tie-break: one with "field" in its title, otherwise the last one. */
    public function tieBreakSection(Exam $exam): ?ExamSection
    {
        $exam->loadMissing('sections');

        return $exam->sections->first(fn ($s) => str_contains(mb_strtolower($s->title), 'field')) ?? $exam->sections->last();
    }

    private function compare(Result $a, Result $b, Exam $exam, ?ExamSection $tieSection): int
    {
        $c = (float) $b->total_marks <=> (float) $a->total_marks;
        if ($c !== 0) {
            return $c;
        }

        if ($exam->tie_break_rule === 'field_section_score' && $tieSection) {
            $ma = (float) ($a->sections->firstWhere('exam_section_id', $tieSection->id)?->marks ?? 0);
            $mb = (float) ($b->sections->firstWhere('exam_section_id', $tieSection->id)?->marks ?? 0);
            $c = $mb <=> $ma;
            if ($c !== 0) {
                return $c;
            }
        }

        if ($exam->tie_break_rule === 'earliest_submission') {
            return ($a->attempt->submitted_at?->getTimestamp() ?? 0) <=> ($b->attempt->submitted_at?->getTimestamp() ?? 0);
        }

        return 0;
    }
}
