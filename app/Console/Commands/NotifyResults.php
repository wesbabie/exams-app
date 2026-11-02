<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Services\ResultNotifier;
use Illuminate\Console\Command;

class NotifyResults extends Command
{
    protected $signature = 'exams:notify-results {exam : The exam id}';

    protected $description = 'Email candidates that the released results of an exam are ready (skips anyone already told)';

    public function handle(ResultNotifier $notifier): int
    {
        $exam = Exam::find($this->argument('exam'));

        if (! $exam) {
            $this->error('No exam with that id.');

            return self::FAILURE;
        }

        if (! $exam->results_released_at) {
            $this->error('This exam\'s results have not been released.');

            return self::FAILURE;
        }

        if ($exam->result_release === 'hidden') {
            $this->warn('This exam is set to show candidates nothing, so nobody is emailed.');

            return self::SUCCESS;
        }

        $done = $notifier->notify($exam);
        $this->info("Sent {$done['sent']}, failed {$done['failed']}, no email address {$done['no_email']}.");

        return $done['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
