<?php

use App\Mail\ResultsReleasedMail;
use App\Models\Applicant;
use App\Models\Attempt;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamPaper;
use App\Models\ExamRegistration;
use App\Models\ExamSection;
use App\Models\ExamSession;
use App\Models\Intake;
use App\Models\Result;
use App\Models\ResultNotification;
use App\Models\SectionResult;
use App\Models\StaffCandidate;
use App\Models\User;
use App\Services\ResultNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/** An exam that has been marked but not released, with candidates who do and do not have an email. */
function markedExam(string $mode = 'full_breakdown'): array
{
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'Nurse I', 'code' => 'N1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion Exam', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50, 'pass_mark_type' => 'percentage', 'result_release' => $mode]);
    $section = ExamSection::create(['exam_id' => $exam->id, 'position' => 1, 'title' => 'General', 'question_count' => 10, 'seconds_per_question' => 60, 'marks_per_question' => 1]);
    $sitting = ExamSession::create(['exam_id' => $exam->id, 'name' => 'Session 1', 'capacity' => 10, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHours(2)]);
    $paper = ExamPaper::create(['exam_session_id' => $sitting->id, 'generated_at' => now()->subDays(3), 'frozen_at' => now()->subDays(3)]);

    $sat = function (StaffCandidate|Applicant $who, string $type, int $marks) use ($exam, $sitting, $paper, $section) {
        $reg = ExamRegistration::create(['exam_id' => $exam->id, 'exam_session_id' => $sitting->id, 'candidate_type' => $type, 'candidate_id' => $who->id, 'seat_number' => (string) $who->id, 'status' => 'completed']);
        $attempt = Attempt::create(['exam_registration_id' => $reg->id, 'exam_paper_id' => $paper->id, 'status' => 'submitted', 'started_at' => now()->subDays(2)]);
        $result = Result::create(['attempt_id' => $attempt->id, 'exam_registration_id' => $reg->id, 'exam_id' => $exam->id, 'total_marks' => $marks, 'max_marks' => 10, 'percentage' => $marks * 10, 'passed' => $marks >= 5]);
        SectionResult::create(['result_id' => $result->id, 'exam_section_id' => $section->id, 'marks' => $marks, 'max_marks' => 10]);

        return $reg;
    };

    $ada = StaffCandidate::create(['file_number' => 'F/1', 'surname' => 'Okoro', 'first_name' => 'Ada', 'cadre' => 'N', 'level' => 'I', 'email' => 'ada@example.com']);
    $bola = StaffCandidate::create(['file_number' => 'F/2', 'surname' => 'Danjuma', 'first_name' => 'Bola', 'cadre' => 'N', 'level' => 'I', 'email' => null]);
    $intake = Intake::create(['name' => 'I', 'year' => 2026, 'opens_at' => now()->subMonth(), 'closes_at' => now()->subWeek()]);
    $chidi = Applicant::create(['intake_id' => $intake->id, 'exam_category_id' => $category->id, 'application_number' => 'INT/2026/0001', 'surname' => 'Eze', 'first_name' => 'Chidi', 'email' => 'chidi@example.com', 'phone' => '08011110000', 'password' => 'Password123', 'status' => 'approved', 'email_verified_at' => now()]);

    $sat($ada, 'staff', 8);
    $sat($bola, 'staff', 3);
    $sat($chidi, 'applicant', 6);

    return compact('exam');
}

function examOfficer(string $role = 'exam_officer'): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true, 'must_change_password' => false])->save();

    return $user;
}

it('emails candidates when results are released, and says how to open the result', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();

    $this->actingAs(examOfficer())->post(route('results.exams.release', $exam), ['notify' => 1])->assertSessionHasNoErrors();

    Mail::assertSent(ResultsReleasedMail::class, 2);
    Mail::assertSent(ResultsReleasedMail::class, fn ($m) => $m->hasTo('ada@example.com') && $m->url === route('myresults.request') && str_contains($m->howTo, 'file number'));
    Mail::assertSent(ResultsReleasedMail::class, fn ($m) => $m->hasTo('chidi@example.com') && $m->url === route('intern.login') && str_contains($m->howTo, 'Sign in'));
});

it('never puts the result itself in the email', function () {
    ['exam' => $exam] = markedExam();
    $mail = new ResultsReleasedMail('Ada', $exam->title, route('myresults.request'), 'Open the link.');

    $mail->assertSeeInHtml('Promotion Exam');
    $mail->assertDontSeeInHtml('Passed');
    $mail->assertDontSeeInHtml('80%');
    $mail->assertDontSeeInHtml('marks');
    $mail->assertSeeInHtml('See my result');
});

it('records who was told, who has no email address and who is still waiting', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();

    $this->actingAs(examOfficer())->post(route('results.exams.release', $exam), ['notify' => 1]);

    $summary = app(ResultNotifier::class)->summary($exam->fresh());
    expect($summary)->toMatchArray(['sent' => 2, 'failed' => 0, 'no_email' => 1, 'waiting' => 0, 'total' => 3]);
    expect(ResultNotification::where('status', 'no_email')->count())->toBe(1);
});

it('does not email anyone when the officer unticks the box', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();

    $this->actingAs(examOfficer())->post(route('results.exams.release', $exam))->assertSessionHasNoErrors();

    Mail::assertNothingSent();
    expect(ResultNotification::count())->toBe(0)->and($exam->fresh()->results_released_at)->not->toBeNull();
});

it('does not email anyone when the exam shows candidates nothing', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam('hidden');

    $this->actingAs(examOfficer())->post(route('results.exams.release', $exam), ['notify' => 1]);
    $this->actingAs(examOfficer())->post(route('results.exams.notify', $exam))->assertSessionHas('error');

    Mail::assertNothingSent();
});

it('never emails the same candidate twice for one release', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();
    $officer = examOfficer();

    $this->actingAs($officer)->post(route('results.exams.release', $exam), ['notify' => 1]);
    $this->actingAs($officer)->post(route('results.exams.notify', $exam))->assertSessionHas('status');
    $this->actingAs($officer)->post(route('results.exams.notify', $exam));

    Mail::assertSent(ResultsReleasedMail::class, 2);
});

it('can be run again for the people a failed run missed', function () {
    ['exam' => $exam] = markedExam();
    $this->actingAs(examOfficer())->post(route('results.exams.release', $exam));
    $exam = $exam->fresh();

    // The mail server is down for the first run: nothing is listening on that port
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 2]);
    $first = app(ResultNotifier::class)->notify($exam);
    expect($first)->toMatchArray(['sent' => 0, 'failed' => 2, 'no_email' => 1]);
    expect(ResultNotification::where('status', 'failed')->first()->error)->not->toBeEmpty();

    // It comes back and the same run is repeated
    config(['mail.default' => 'array']);
    Mail::fake();
    $second = app(ResultNotifier::class)->notify($exam);
    expect($second)->toMatchArray(['sent' => 2, 'failed' => 0, 'no_email' => 1]);
    Mail::assertSent(ResultsReleasedMail::class, 2);
    expect(ResultNotification::where('status', 'failed')->count())->toBe(0)->and(ResultNotification::count())->toBe(3);
});

it('tells candidates again after results are withdrawn and released afresh', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();
    $officer = examOfficer();

    $this->actingAs($officer)->post(route('results.exams.release', $exam), ['notify' => 1]);
    $this->actingAs($officer)->post(route('results.exams.withdraw', $exam));
    $this->travel(5)->minutes();
    $this->actingAs($officer)->post(route('results.exams.release', $exam), ['notify' => 1]);

    Mail::assertSent(ResultsReleasedMail::class, 4);
});

it('is something only exam officers and the super admin can do', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();
    $this->actingAs(examOfficer())->post(route('results.exams.release', $exam));

    foreach (['committee', 'invigilator', 'question_author'] as $role) {
        $this->actingAs(examOfficer($role))->post(route('results.exams.notify', $exam))->assertForbidden();
    }
    Mail::assertNothingSent();

    $this->actingAs(examOfficer('super_admin'))->post(route('results.exams.notify', $exam))->assertSessionHas('status');
    Mail::assertSent(ResultsReleasedMail::class, 2);
});

it('will not email before the results are released', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();

    $this->actingAs(examOfficer())->post(route('results.exams.notify', $exam))->assertSessionHas('error');

    Mail::assertNothingSent();
});

it('shows the exam office how many were told and offers to email the rest', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();
    $officer = examOfficer();

    $this->actingAs($officer)->post(route('results.exams.release', $exam), ['notify' => 1]);

    $this->actingAs($officer)->get(route('results.exams.show', $exam))->assertOk()
        ->assertSee('Emails to candidates')->assertSee('2 of 3')->assertSee('1 have no email address')
        ->assertDontSee('Email the ones not yet told');

    // Released without emailing: everyone is still waiting
    ResultNotification::query()->delete();
    $this->actingAs($officer)->get(route('results.exams.show', $exam))->assertSee('3 not emailed yet')->assertSee('Email candidates now');
});

it('offers the email box when releasing, but not for an exam that shows nothing', function () {
    ['exam' => $exam] = markedExam();
    $this->actingAs(examOfficer())->get(route('results.exams.show', $exam))->assertSee('Email candidates that their results are out');

    $exam->update(['result_release' => 'hidden']);
    $this->actingAs(examOfficer())->get(route('results.exams.show', $exam))->assertDontSee('Email candidates that their results are out');
});

it('records the emailing in the audit log', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();
    $officer = examOfficer();
    $this->actingAs($officer)->post(route('results.exams.release', $exam), ['notify' => 1]);
    $this->actingAs($officer)->post(route('results.exams.notify', $exam));

    expect(AuditLog::where('action', 'results.exams.notify')->where('actor_id', $officer->id)->count())->toBe(1)
        ->and(AuditLog::where('action', 'reg.results_released')->firstOrFail()->summary)->toContain('candidates emailed');
});

it('has a command to email candidates from the server', function () {
    Mail::fake();
    ['exam' => $exam] = markedExam();

    $this->artisan('exams:notify-results', ['exam' => $exam->id])->expectsOutputToContain('have not been released')->assertFailed();

    $this->actingAs(examOfficer())->post(route('results.exams.release', $exam));
    $this->artisan('exams:notify-results', ['exam' => $exam->id])->expectsOutputToContain('Sent 2, failed 0, no email address 1')->assertSuccessful();
    $this->artisan('exams:notify-results', ['exam' => $exam->id])->expectsOutputToContain('Sent 0')->assertSuccessful();
    $this->artisan('exams:notify-results', ['exam' => 9999])->assertFailed();
});
