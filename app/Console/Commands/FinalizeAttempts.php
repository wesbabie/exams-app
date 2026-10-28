<?php

namespace App\Console\Commands;

use App\Models\Attempt;
use App\Services\AttemptService;
use Illuminate\Console\Command;

class FinalizeAttempts extends Command
{
    protected $signature = 'exams:finalize-attempts';

    protected $description = 'Submit exams that were left unfinished after their sitting ended (for example a candidate whose computer died).';

    public function handle(AttemptService $service): int
    {
        $count = 0;

        Attempt::where('status', 'in_progress')
            ->with('registration.exam.sections', 'registration.examSession')
            ->get()
            ->each(function (Attempt $attempt) use ($service, &$count) {
                $registration = $attempt->registration;
                $multiplier = (float) ($registration->extra_time_multiplier ?: 1);

                // The sitting's end, plus the longest the exam could still be running, plus ten minutes
                $limit = $registration->examSession->ends_at->copy()
                    ->addSeconds((int) round($registration->exam->totalSeconds() * $multiplier) + 600);

                if (now()->gt($limit)) {
                    $service->finish($attempt, 'time_expired');
                    $count++;
                }
            });

        $this->info("{$count} unfinished attempt(s) submitted.");

        return self::SUCCESS;
    }
}
