<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Development only: a few topics and approved sample questions.
 * Run DemoStaffSeeder and DemoCandidateSeeder first.
 *
 * Run with: php artisan db:seed --class=DemoBankSeeder
 */
class DemoBankSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $author = DB::table('users')->where('email', 'author@exams.test')->value('id');
        $reviewer = DB::table('users')->where('email', 'reviewer@exams.test')->value('id');
        $categoryId = DB::table('exam_categories')->where('code', 'DEMO-I')->value('id');

        $topic = function (string $name, ?int $category) use ($now) {
            $existing = DB::table('topics')->where('name', $name)
                ->when($category, fn ($q) => $q->where('exam_category_id', $category), fn ($q) => $q->whereNull('exam_category_id'))
                ->value('id');

            return $existing ?? DB::table('topics')->insertGetId([
                'name' => $name, 'exam_category_id' => $category, 'created_at' => $now, 'updated_at' => $now,
            ]);
        };

        $gk = $topic('General Knowledge', null);
        $csr = $topic('Civil Service Rules', null);
        $clinical = $categoryId ? $topic('Clinical Practice', $categoryId) : null;

        // [topic id, category id, type, stem, options as [text, correct]]
        $questions = [
            [$gk, null, 'single_choice', 'What is the capital city of Nigeria?', [['Lagos', 0], ['Abuja', 1], ['Kano', 0], ['Port Harcourt', 0]]],
            [$gk, null, 'single_choice', 'Which body conducts general elections in Nigeria?', [['INEC', 1], ['NNPC', 0], ['CBN', 0], ['NIMC', 0]]],
            [$gk, null, 'true_false', 'Nigeria became independent in 1960.', [['True', 1], ['False', 0]]],
            [$csr, null, 'single_choice', 'A public officer has a personal interest in a matter they are handling. They should:', [['Handle it quietly', 0], ['Declare the interest and step aside', 1], ['Ask a junior to decide', 0], ['Do nothing', 0]]],
            [$csr, null, 'single_choice', 'Before proceeding on annual leave, a staff member should:', [['Simply stop coming to work', 0], ['Inform colleagues only', 0], ['Obtain approval from the appropriate authority', 1], ['Wait until the leave ends to report', 0]]],
        ];

        if ($clinical) {
            $questions[] = [$clinical, $categoryId, 'single_choice', 'Which range is normal for an adult’s resting heart rate?', [['30–50 bpm', 0], ['60–100 bpm', 1], ['110–140 bpm', 0], ['150–180 bpm', 0]]];
            $questions[] = [$clinical, $categoryId, 'single_choice', 'Hand hygiene before patient contact mainly helps prevent:', [['Healthcare-associated infection', 1], ['Hypertension', 0], ['Dehydration', 0], ['Anaemia', 0]]];
            $questions[] = [$clinical, $categoryId, 'multi_select', 'Which of these are vital signs?', [['Temperature', 1], ['Pulse', 1], ['Eye colour', 0], ['Respiratory rate', 1]]];
        }

        $added = 0;
        foreach ($questions as [$topicId, $catId, $type, $stem, $options]) {
            if (DB::table('questions')->where('stem', $stem)->exists()) {
                continue;
            }

            $id = DB::table('questions')->insertGetId([
                'topic_id' => $topicId, 'exam_category_id' => $catId, 'type' => $type, 'stem' => $stem,
                'status' => 'approved', 'version' => 1, 'created_by' => $author, 'reviewed_by' => $reviewer,
                'reviewed_at' => $now, 'submitted_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);

            foreach ($options as $i => [$text, $correct]) {
                DB::table('question_options')->insert([
                    'question_id' => $id, 'body' => $text, 'is_correct' => (bool) $correct,
                    'position' => $i + 1, 'pin_last' => false, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $added++;
        }

        $this->command->info("Question bank demo ready. {$added} sample question(s) added.");
    }
}
