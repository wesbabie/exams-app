<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates (or resets) one demo exam, an open sitting and an admitted staff candidate so the candidate pages can be tried.
 * Safe to run again: it reopens the sitting, unlocks the candidate and sets the PIN back to 123456.
 *
 * Run with: php artisan db:seed --class=DemoCandidateSeeder
 */
class DemoCandidateSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $candidate = DB::table('staff_candidates')->where('file_number', 'TEST/0001')->first();

        if ($candidate) {
            $registration = DB::table('exam_registrations')
                ->where('candidate_type', 'staff')->where('candidate_id', $candidate->id)->first();

            DB::table('exam_sessions')->where('id', $registration->exam_session_id)->update([
                'starts_at' => $now->copy()->subHour(), 'ends_at' => $now->copy()->addHours(3),
                'status' => 'open', 'updated_at' => $now,
            ]);

            DB::table('exam_registrations')->where('id', $registration->id)->update([
                'admitted_at' => $now, 'locked_at' => null, 'pin_failed_attempts' => 0,
                'pin_hash' => Hash::make('123456'), 'pin_issued_at' => $now,
                'pin_expires_at' => $now->copy()->addHours(4), 'updated_at' => $now,
            ]);

            $this->sections($registration->exam_id);
            $this->command->info('Demo reset. File number: TEST/0001   PIN: 123456');

            return;
        }

        $categoryId = DB::table('exam_categories')->insertGetId([
            'kind' => 'staff_cadre', 'name' => 'Demo Cadre I', 'code' => 'DEMO-I',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $examId = DB::table('exams')->insertGetId([
            'exam_category_id' => $categoryId, 'title' => 'Demo Promotion Examination',
            'pass_mark' => 50, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $sessionId = DB::table('exam_sessions')->insertGetId([
            'exam_id' => $examId, 'name' => 'Demo sitting', 'venue' => 'ICT Lab',
            'starts_at' => $now->copy()->subHour(), 'ends_at' => $now->copy()->addHours(3),
            'status' => 'open', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $candidateId = DB::table('staff_candidates')->insertGetId([
            'file_number' => 'TEST/0001', 'surname' => 'Demo', 'first_name' => 'Candidate',
            'cadre' => 'Nursing Officer', 'level' => 'I', 'department' => 'Nursing Services',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('exam_registrations')->insert([
            'exam_id' => $examId, 'exam_session_id' => $sessionId,
            'candidate_type' => 'staff', 'candidate_id' => $candidateId,
            'status' => 'admitted', 'admitted_at' => $now,
            'pin_hash' => Hash::make('123456'), 'pin_issued_at' => $now,
            'pin_expires_at' => $now->copy()->addHours(4),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->sections($examId);
        $this->command->info('Demo candidate ready. File number: TEST/0001   PIN: 123456');
    }

    private function sections(int $examId): void
    {
        if (DB::table('exam_sections')->where('exam_id', $examId)->exists()) {
            return;
        }

        $now = now();
        $rows = [
            ['General Knowledge', 20], ['Civil Service Rules', 20], ['Field-Related', 30],
        ];

        foreach ($rows as $i => [$title, $count]) {
            DB::table('exam_sections')->insert([
                'exam_id' => $examId, 'title' => $title, 'position' => $i + 1,
                'question_count' => $count, 'seconds_per_question' => 60, 'marks_per_question' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
