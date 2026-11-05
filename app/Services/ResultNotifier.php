<?php

namespace App\Services;

use App\Mail\ResultsReleasedMail;
use App\Models\Exam;
use App\Models\Result;
use App\Models\ResultNotification;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells candidates by email that their result is out. The email never carries the result itself.
 * Each candidate is recorded per release, so a run that stops half way can simply be run again:
 * it skips everyone already told and tries the rest.
 */
class ResultNotifier
{
    /** @return array{sent: int, failed: int, no_email: int} what this run did */
    public function notify(Exam $exam): array
    {
        $done = ['sent' => 0, 'failed' => 0, 'no_email' => 0];

        $stamp = $exam->results_released_at;

        // Nothing to look at yet, or the exam is set to show candidates nothing
        if (! $stamp || $exam->result_release === 'hidden') {
            return $done;
        }

        @set_time_limit(0); // a hundred emails take longer than a web request normally may

        Result::where('exam_id', $exam->id)->with('registration')->orderBy('id')->each(function (Result $result) use ($exam, $stamp, &$done) {
            $registration = $result->registration;
            $candidate = $registration?->candidate;

            if (! $registration || ! $candidate) {
                return;
            }

            $row = ResultNotification::firstOrNew(['exam_registration_id' => $registration->id, 'released_at' => $stamp]);

            if ($row->status === 'sent') {
                return; // already told for this release
            }

            $row->exam_id = $exam->id;
            $row->email = $candidate->email;

            if (blank($candidate->email)) {
                $row->fill(['status' => 'no_email', 'error' => null])->save();
                $done['no_email']++;

                return;
            }

            [$url, $howTo] = $registration->candidate_type === 'applicant'
                ? [route('intern.login'), 'Sign in to your internship application to see it.']
                : [route('myresults.request'), 'Open the link and enter your file number with the PIN from your exam slip. If you lost the PIN, you can ask for a one-time code by email on the same page.'];

            try {
                Mail::to($candidate->email)->send(new ResultsReleasedMail($candidate->first_name, $exam->title, $url, $howTo));
                $row->fill(['status' => 'sent', 'error' => null, 'sent_at' => now()])->save();
                $done['sent']++;
            } catch (Throwable $e) {
                report($e);
                $row->fill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250)])->save();
                $done['failed']++;
            }
        });

        return $done;
    }

    /** @return array{sent: int, failed: int, no_email: int, waiting: int, total: int} for the current release */
    public function summary(Exam $exam): array
    {
        $total = Result::where('exam_id', $exam->id)->count();

        if (! $exam->results_released_at) {
            return ['sent' => 0, 'failed' => 0, 'no_email' => 0, 'waiting' => $total, 'total' => $total];
        }

        $counts = ResultNotification::where('exam_id', $exam->id)->where('released_at', $exam->results_released_at)
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $sent = (int) ($counts['sent'] ?? 0);
        $failed = (int) ($counts['failed'] ?? 0);
        $none = (int) ($counts['no_email'] ?? 0);

        return ['sent' => $sent, 'failed' => $failed, 'no_email' => $none, 'waiting' => max(0, $total - $sent - $failed - $none), 'total' => $total];
    }
}
