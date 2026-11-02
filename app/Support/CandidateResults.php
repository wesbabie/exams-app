<?php

namespace App\Support;

use App\Models\ExamRegistration;
use App\Models\Result;
use App\Models\StaffCandidate;
use Illuminate\Support\Collection;

/**
 * Which results a candidate may read. A result is visible only while its exam's results are released
 * and the exam is set to show candidates something. Withdrawing the release hides it again at once.
 */
final class CandidateResults
{
    /** @param  iterable<int>  $registrationIds @return Collection<int, Result> newest release first */
    public static function forRegistrations(iterable $registrationIds): Collection
    {
        return Result::whereIn('exam_registration_id', collect($registrationIds)->all())
            ->with(['exam.sections', 'sections', 'attempt'])
            ->get()
            ->filter(fn (Result $r) => $r->exam && $r->exam->results_released_at && $r->exam->result_release !== 'hidden')
            ->sortByDesc(fn (Result $r) => $r->exam->results_released_at)
            ->values();
    }

    public static function forStaffCandidate(StaffCandidate $candidate): Collection
    {
        return self::forRegistrations(
            ExamRegistration::where('candidate_type', 'staff')->where('candidate_id', $candidate->id)->pluck('id')
        );
    }
}
