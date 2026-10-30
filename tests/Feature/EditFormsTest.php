<?php

use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamSection;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function staffAs(string $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true, 'must_change_password' => false])->save();

    return $user;
}

function formFixtures(): array
{
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'Nursing Officer I', 'code' => 'NO1']);
    $topic = Topic::create(['name' => 'General Knowledge', 'exam_category_id' => null]);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion Exam', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50]);
    $section = ExamSection::create(['exam_id' => $exam->id, 'position' => 1, 'title' => 'General Knowledge', 'question_count' => 5, 'seconds_per_question' => 60, 'marks_per_question' => 1]);
    $question = Question::create(['topic_id' => $topic->id, 'type' => 'single_choice', 'stem' => 'What is the capital of Nigeria?', 'status' => 'draft', 'version' => 1]);
    foreach (['Lagos', 'Abuja'] as $i => $body) {
        QuestionOption::create(['question_id' => $question->id, 'body' => $body, 'is_correct' => $i === 1, 'position' => $i + 1]);
    }

    return compact('category', 'topic', 'exam', 'section', 'question');
}

it('renders the exam form for a new and an existing exam', function () {
    $f = formFixtures();
    $officer = staffAs('exam_officer');

    $this->actingAs($officer)->get(route('officer.exams.create'))
        ->assertOk()->assertSee('New exam')->assertSee('id="exam-form"', false)->assertSee('Exam totals');

    $this->actingAs($officer)->get(route('officer.exams.edit', $f['exam']))
        ->assertOk()->assertSee('Edit exam')->assertSee('Promotion Exam');
});

it('still saves an exam with its sections from the form fields', function () {
    $f = formFixtures();

    $this->actingAs(staffAs('exam_officer'))->post(route('officer.exams.store'), [
        'title' => 'New Promotion Exam', 'exam_category_id' => $f['category']->id, 'cycle_year' => 2027, 'paper_mode' => 'fixed_set',
        'pass_mark' => 50, 'pass_mark_type' => 'percentage', 'negative_mark_fraction' => 0, 'result_release' => 'hidden',
        'shuffle_questions' => 1, 'shuffle_options' => 1, 'forward_only' => 1,
        'sections' => [
            0 => ['id' => '', 'title' => 'General Knowledge', 'question_count' => 20, 'seconds_per_question' => 60, 'marks_per_question' => 1, 'instructions' => ''],
            1 => ['id' => '', 'title' => 'Field-Related', 'question_count' => 30, 'seconds_per_question' => 45, 'marks_per_question' => 1, 'instructions' => ''],
        ],
    ])->assertSessionHasNoErrors();

    $exam = Exam::where('title', 'New Promotion Exam')->firstOrFail();
    expect($exam->sections()->count())->toBe(2);
});

it('renders the question form for a new and an existing question', function () {
    $f = formFixtures();
    $author = staffAs('question_author');

    $this->actingAs($author)->get(route('bank.questions.create'))
        ->assertOk()->assertSee('New question')->assertSee('id="question-form"', false)->assertSee('How candidates see it');

    $question = $f['question'];
    $question->update(['created_by' => $author->id]);
    $this->actingAs($author)->get(route('bank.questions.edit', $question))
        ->assertOk()->assertSee('Edit question')->assertSee('What is the capital of Nigeria?')->assertSee('Abuja');
});

it('renders the remaining edit pages', function () {
    $f = formFixtures();
    $officer = staffAs('exam_officer');

    foreach ([
        route('officer.exams.sections.questions.edit', [$f['exam'], $f['section']]),
        route('officer.categories.edit', $f['category']),
        route('bank.topics.edit', $f['topic']),
        route('bank.import.create'),
        route('account.password.edit'),
    ] as $url) {
        $this->actingAs($officer)->get($url)->assertOk();
    }

    $admin = staffAs('super_admin');
    $this->actingAs($admin)->get(route('admin.staff.create'))->assertOk()->assertSee('New account');
    $this->actingAs($admin)->get(route('admin.staff.edit', $officer))->assertOk();
});
