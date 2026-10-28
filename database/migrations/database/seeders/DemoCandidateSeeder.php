<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates one demo exam, an open sitting and an admitted staff candidate so the login page can be tried.
 * Run with: php artisan db:seed --class=DemoCandidateSeeder
 */
class DemoCandidateSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('staff_candidates')->where('file_number', 'TEST/0001')->exists()) {
            $this->command->warn('Demo candidate already exists.');

            return;
        }

        $now = now();

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
            'cadre' => 'Demo Cadre', 'level' => 'I', 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('exam_registrations')->insert([
            'exam_id' => $examId, 'exam_session_id' => $sessionId,
            'candidate_type' => 'staff', 'candidate_id' => $candidateId,
            'status' => 'admitted', 'admitted_at' => $now,
            'pin_hash' => Hash::make('123456'), 'pin_issued_at' => $now,
            'pin_expires_at' => $now->copy()->addHours(4),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->command->info('Demo candidate ready. File number: TEST/0001   PIN: 123456');
    }
}
