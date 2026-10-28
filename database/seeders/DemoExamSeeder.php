<?php

namespace Database\Seeders;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamRegistration;
use App\Models\ExamSection;
use App\Models\ExamSectionQuestion;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\StaffCandidate;
use App\Models\Topic;
use App\Models\User;
use App\Services\PaperGenerator;
use Illuminate\Database\Seeder;

/**
 * Development only: a small complete exam with a frozen paper, an open sitting and two candidates
 * assigned to seats, ready for check-in and a full run-through.
 * Needs DemoStaffSeeder and DemoBankSeeder first. Safe to run again: it resets the sitting and the candidates.
 *
 * Run with: php artisan db:seed --class=DemoExamSeeder
 */
class DemoExamSeeder extends Seeder
{
    public function run(): void
    {
        $title = 'Demo Exam (ready to run)';

        $category = ExamCategory::firstOrCreate(
            ['code' => 'DEMO-I'],
            ['kind' => 'staff_cadre', 'name' => 'Demo Cadre I', 'is_active' => true]
        );

        $exam = Exam::where('title', $title)->first();

        if (! $exam) {
            $exam = Exam::create([
                'exam_category_id' => $category->id, 'title' => $title, 'paper_mode' => 'fixed_set',
                'pass_mark' => 50, 'pass_mark_type' => 'percentage', 'negative_mark_fraction' => 0,
                'shuffle_questions' => true, 'shuffle_options' => true, 'forward_only' => true,
                'result_release' => 'hidden', 'status' => 'approved',
                'created_by' => User::where('email', 'officer@exams.test')->value('id'),
                'approved_by' => User::where('email', 'admin@exams.test')->value('id'),
                'approved_at' => now(),
            ]);

            $plan = [
                ['General Knowledge', 'General Knowledge', 3, 30],
                ['Civil Service Rules', 'Civil Service Rules', 2, 30],
                ['Field-Related', 'Clinical Practice', 3, 45],
            ];

            foreach ($plan as $i => [$sectionTitle, $topicName, $count, $seconds]) {
                $topic = Topic::where('name', $topicName)->first();
                $ids = $topic ? Question::where('status', 'approved')->where('topic_id', $topic->id)->orderBy('id')->limit($count)->pluck('id') : collect();

                if ($ids->count() < $count) {
                    $exam->forceDelete();
                    $this->command->error("The question bank has too few approved questions in \"{$topicName}\". Run DemoBankSeeder first.");

                    return;
                }

                $section = ExamSection::create([
                    'exam_id' => $exam->id, 'title' => $sectionTitle, 'position' => $i + 1,
                    'question_count' => $count, 'seconds_per_question' => $seconds, 'marks_per_question' => 1,
                ]);

                foreach ($ids as $position => $questionId) {
                    ExamSectionQuestion::create(['exam_section_id' => $section->id, 'question_id' => $questionId, 'position' => $position + 1]);
                }
            }
        }

        // An open sitting around the current time
        $sitting = ExamSession::updateOrCreate(
            ['exam_id' => $exam->id, 'name' => 'Demo sitting'],
            ['venue' => 'ICT Lab', 'starts_at' => now()->subMinutes(30), 'ends_at' => now()->addHours(3), 'capacity' => 20, 'status' => 'scheduled']
        );

        // Two candidates, put back to "assigned" with no attempt, PIN or admission
        foreach ([['TEST/0002', 'Ada', 'Demo', 1], ['TEST/0003', 'Bola', 'Demo', 2]] as [$fileNumber, $first, $last, $seat]) {
            $candidate = StaffCandidate::updateOrCreate(
                ['file_number' => $fileNumber],
                ['surname' => $last, 'first_name' => $first, 'cadre' => 'Demo Cadre I', 'department' => 'Nursing Services']
            );

            $registration = ExamRegistration::where('exam_id', $exam->id)
                ->where('candidate_type', 'staff')->where('candidate_id', $candidate->id)->first();

            if ($registration) {
                Attempt::where('exam_registration_id', $registration->id)->delete();
            }

            ExamRegistration::updateOrCreate(
                ['exam_id' => $exam->id, 'candidate_type' => 'staff', 'candidate_id' => $candidate->id],
                [
                    'exam_session_id' => $sitting->id, 'seat_number' => (string) $seat, 'status' => 'assigned', 'eligible_list_id' => null,
                    'pin_hash' => null, 'pin_issued_at' => null, 'pin_expires_at' => null, 'pin_failed_attempts' => 0,
                    'locked_at' => null, 'checked_in_at' => null, 'checked_in_by' => null, 'admitted_at' => null,
                ]
            );
        }

        // A frozen paper, if the sitting does not have one yet
        $generator = app(PaperGenerator::class);
        $paper = $sitting->fresh()->paper;

        if (! $paper) {
            $paper = $generator->generate($sitting);
        }
        if (! $paper->isFrozen()) {
            $generator->freeze($paper);
        }

        $this->command->info('Demo exam ready: "'.$title.'", sitting "Demo sitting", paper frozen.');
        $this->command->info('Candidates (not yet checked in): TEST/0002 and TEST/0003. Check them in as invigilator@exams.test.');
    }
}
