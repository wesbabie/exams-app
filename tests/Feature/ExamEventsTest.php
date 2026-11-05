<?php

use App\Http\Middleware\CandidateAuth;
use App\Models\Attempt;
use App\Models\AttemptEvent;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamPaper;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\StaffCandidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A candidate who is part-way through the exam, with an open sitting. */
function writing(): array
{
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'Nurse I', 'code' => 'N1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion Exam', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50]);
    $sitting = ExamSession::create(['exam_id' => $exam->id, 'name' => 'Session 1', 'capacity' => 10, 'starts_at' => now()->subMinutes(30), 'ends_at' => now()->addHours(2)]);
    $paper = ExamPaper::create(['exam_session_id' => $sitting->id, 'generated_at' => now()->subHour(), 'frozen_at' => now()->subHour()]);
    $candidate = StaffCandidate::create(['file_number' => 'F/1', 'surname' => 'Okoro', 'first_name' => 'Ada', 'cadre' => 'N', 'level' => 'I']);
    $registration = ExamRegistration::create(['exam_id' => $exam->id, 'exam_session_id' => $sitting->id, 'candidate_type' => 'staff', 'candidate_id' => $candidate->id, 'seat_number' => '1', 'status' => 'in_progress', 'admitted_at' => now()->subMinutes(20)]);
    $attempt = Attempt::create(['exam_registration_id' => $registration->id, 'exam_paper_id' => $paper->id, 'status' => 'in_progress', 'started_at' => now()->subMinutes(10)]);

    return compact('registration', 'attempt');
}

function reportEvent(array $registration, array $body)
{
    return test()->withSession([CandidateAuth::SESSION_KEY => $registration['registration']->id])->postJson(route('candidate.exam.event'), $body);
}

it('records leaving the tab and coming back, with how long they were away', function () {
    $w = writing();

    reportEvent($w, ['type' => 'tab_hidden'])->assertOk();
    reportEvent($w, ['type' => 'tab_visible', 'away_seconds' => 12])->assertOk();

    $events = AttemptEvent::where('attempt_id', $w['attempt']->id)->orderBy('id')->get();
    expect($events->pluck('event_type')->all())->toBe(['tab_hidden', 'tab_visible'])
        ->and($events[0]->meta)->toBeNull()
        ->and($events[1]->meta)->toBe(['away_seconds' => 12]);
});

it('keeps the time away within sensible limits and ignores junk', function () {
    $w = writing();

    reportEvent($w, ['type' => 'tab_visible', 'away_seconds' => -5]);
    reportEvent($w, ['type' => 'tab_visible', 'away_seconds' => 999999999]);
    reportEvent($w, ['type' => 'tab_visible', 'away_seconds' => 'a long time']);
    reportEvent($w, ['type' => 'tab_hidden', 'away_seconds' => 30]); // only a return carries a duration
    reportEvent($w, ['type' => 'made_up']);
    reportEvent($w, ['type' => 'submitted']);                         // a candidate cannot fake system events

    $events = AttemptEvent::where('attempt_id', $w['attempt']->id)->orderBy('id')->get();
    expect($events->pluck('event_type')->all())->toBe(['tab_visible', 'tab_visible', 'tab_visible', 'tab_hidden'])
        ->and($events[0]->meta)->toBe(['away_seconds' => 0])
        ->and($events[1]->meta)->toBe(['away_seconds' => 86400])
        ->and($events[2]->meta)->toBeNull()
        ->and($events[3]->meta)->toBeNull();
});

it('records nothing for someone who is not part-way through an exam', function () {
    $w = writing();
    $w['attempt']->update(['status' => 'submitted']);

    reportEvent($w, ['type' => 'tab_hidden'])->assertOk();

    expect(AttemptEvent::count())->toBe(0);
});

it('only counts a flag when the candidate really left, and shows how long', function () {
    $w = writing();
    AttemptEvent::create(['attempt_id' => $w['attempt']->id, 'event_type' => 'started', 'created_at' => now()->subMinutes(9)]);
    AttemptEvent::create(['attempt_id' => $w['attempt']->id, 'event_type' => 'tab_hidden', 'created_at' => now()->subMinutes(5)]);
    AttemptEvent::create(['attempt_id' => $w['attempt']->id, 'event_type' => 'tab_visible', 'meta' => ['away_seconds' => 12], 'created_at' => now()->subMinutes(5)->addSeconds(12)]);

    $staff = User::factory()->create();
    $staff->forceFill(['role' => 'invigilator', 'is_active' => true, 'must_change_password' => false])->save();

    $this->actingAs($staff)->get(route('registrations.history', $w['registration']))->assertOk()
        ->assertSeeInOrder(['Started the exam', 'Left the exam tab', 'Came back to the exam tab', 'Away for 12 seconds'])
        ->assertSee('Times left the exam tab');
});

it('keeps the exam page from reporting its own question changes as leaving the tab', function () {
    // The browser calls a page "hidden" while it is replaced, so the script must wait and must know it is leaving.
    $page = file_get_contents(resource_path('views/candidate/exam/question.blade.php'));

    expect($page)->toContain('GRACE_MS')
        ->and($page)->toContain('leaving = true')
        ->and($page)->toContain("addEventListener('pagehide'")
        ->and($page)->not->toContain("send(document.hidden ? 'tab_hidden'");
});
