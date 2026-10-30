<?php

use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\StaffCandidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function invigilator(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => 'invigilator', 'is_active' => true, 'must_change_password' => false])->save();

    return $user;
}

function sittingWith(array $people): ExamSession
{
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'Nursing Officer I', 'code' => 'NO1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion Exam', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50]);
    $sitting = ExamSession::create(['exam_id' => $exam->id, 'name' => 'Session 1', 'venue' => 'ICT Lab', 'capacity' => 20, 'starts_at' => now()->addMinutes(30), 'ends_at' => now()->addHours(3)]);

    foreach ($people as $i => [$file, $surname, $status]) {
        $c = StaffCandidate::create(['file_number' => $file, 'surname' => $surname, 'first_name' => 'Test', 'cadre' => 'Nursing Officer', 'level' => 'I']);
        ExamRegistration::create([
            'exam_id' => $exam->id, 'exam_session_id' => $sitting->id, 'candidate_type' => 'staff', 'candidate_id' => $c->id,
            'seat_number' => (string) ($i + 1), 'status' => $status,
            'checked_in_at' => in_array($status, ['checked_in', 'admitted'], true) ? now() : null,
        ]);
    }

    return $sitting;
}

it('lists the sittings an invigilator can open', function () {
    $sitting = sittingWith([['F/001', 'Adeyemi', 'assigned']]);
    $sitting->update(['starts_at' => now()->addMinutes(20)]);

    $this->actingAs(invigilator())->get(route('invigilator.sittings.index'))
        ->assertOk()->assertSee('Promotion Exam')->assertSee('Check-in open');
});

it('offers the right next action for each candidate stage', function () {
    $sitting = sittingWith([
        ['F/001', 'Arrived', 'assigned'],
        ['F/002', 'Waiting', 'checked_in'],
        ['F/003', 'Inside', 'admitted'],
    ]);

    $this->actingAs(invigilator())->get(route('invigilator.sittings.show', $sitting))
        ->assertOk()
        ->assertSee('Check in')->assertSee('ID checked')
        ->assertSee('Admit all checked in (1)')
        ->assertSee('Withdraw admission')
        ->assertSee('F/001')->assertSee('F/002')->assertSee('F/003');
});

it('finds a candidate by file number', function () {
    $sitting = sittingWith([['F/001', 'Adeyemi', 'assigned'], ['F/002', 'Bello', 'assigned']]);

    $this->actingAs(invigilator())->get(route('invigilator.sittings.show', [$sitting, 'q' => 'F/002']))
        ->assertOk()->assertSee('Bello')->assertDontSee('Adeyemi');
});

it('still requires the ID check before checking someone in', function () {
    $sitting = sittingWith([['F/001', 'Adeyemi', 'assigned']]);
    $registration = ExamRegistration::firstOrFail();

    $this->actingAs(invigilator())->post(route('invigilator.check-in', $registration))->assertSessionHasErrors();
    expect($registration->fresh()->status)->toBe('assigned');

    $this->actingAs(invigilator())->post(route('invigilator.check-in', $registration), ['identity_confirmed' => 1])->assertRedirect();
    expect($registration->fresh()->status)->toBe('checked_in');
});

it('shows the PIN once on the slip', function () {
    sittingWith([['F/001', 'Adeyemi', 'assigned']]);
    $registration = ExamRegistration::firstOrFail();
    $staff = invigilator();

    $this->actingAs($staff)->followingRedirects()
        ->post(route('invigilator.check-in', $registration), ['identity_confirmed' => 1])
        ->assertOk()->assertSee('Your PIN')->assertSee('Print slip');

    $this->actingAs($staff)->get(route('invigilator.slip', $registration))->assertSee('only shown once');
});
