<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\Intake;
use Illuminate\Database\Seeder;

/**
 * Opens a registration window and creates a demo discipline and exam, so the intern portal can be tried.
 * Safe to run again.
 *
 * Run with: php artisan db:seed --class=DemoInternSeeder
 */
class DemoInternSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) now()->year;

        Intake::updateOrCreate(['name' => "Demo Internship Intake {$year}"], [
            'year' => $year, 'opens_at' => now()->subDay(), 'closes_at' => now()->addMonth(),
        ]);

        $category = ExamCategory::firstOrCreate(['code' => 'DEMO-PHARM'], [
            'kind' => 'intern_discipline', 'name' => 'Demo Pharmacy', 'is_active' => true,
        ]);

        Exam::firstOrCreate(['exam_category_id' => $category->id, 'cycle_year' => $year], [
            'title' => "Demo Pharmacy Entrance Exam {$year}", 'paper_mode' => 'pool_draw', 'pass_mark' => 50,
        ]);

        $this->command?->info("Intake open. Register at /intern/register. Discipline: Demo Pharmacy.");
    }
}
