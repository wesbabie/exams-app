<?php

use App\Http\Middleware\CandidateAuth;
use App\Models\Applicant;
use App\Models\Attempt;
use App\Models\AttemptQuestion;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamPaper;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\Intake;
use App\Models\Setting;
use App\Models\StaffCandidate;
use App\Models\User;
use App\Services\AttemptService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function settingsUser(string $role = 'super_admin'): User
{
    $user = User::factory()->create(['name' => 'Chief Admin']);
    $user->forceFill(['role' => $role, 'is_active' => true, 'must_change_password' => false])->save();

    return $user;
}

/** Every setting at its standard value, in the form's field names. */
function standardForm(array $override = []): array
{
    $form = [];

    foreach (config('settings.items') as $key => $d) {
        $form['s_'.str_replace('.', '__', $key)] = $d['default'];
    }

    foreach ($override as $key => $value) {
        $form['s_'.str_replace('.', '__', $key)] = $value;
    }

    return $form;
}

function saveSettings(array $override = [], ?User $as = null)
{
    return test()->actingAs($as ?? settingsUser())->put(route('admin.settings.update'), standardForm($override));
}

/** A candidate ready to be checked in, whose sitting starts in the given number of minutes. */
function waitingCandidate(int $startsInMinutes): ExamRegistration
{
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'Nurse I', 'code' => 'N1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion Exam', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50]);
    $sitting = ExamSession::create(['exam_id' => $exam->id, 'name' => 'Session 1', 'capacity' => 10, 'starts_at' => now()->addMinutes($startsInMinutes), 'ends_at' => now()->addMinutes($startsInMinutes + 120)]);
    $c = StaffCandidate::create(['file_number' => 'F/1', 'surname' => 'Okoro', 'first_name' => 'Ada', 'cadre' => 'N', 'level' => 'I']);

    return ExamRegistration::create(['exam_id' => $exam->id, 'exam_session_id' => $sitting->id, 'candidate_type' => 'staff', 'candidate_id' => $c->id, 'seat_number' => '1', 'status' => 'assigned']);
}

// ---- The standards are the rules the system always followed

it('has standard values that match how the system behaved before settings existed', function () {
    $standard = collect(config('settings.items'))->map(fn ($d) => $d['default'])->all();

    expect($standard)->toMatchArray([
        'checkin.opens_hours_before' => 2, 'exam.pin_grace_minutes' => 60, 'exam.max_wrong_pins' => 5, 'exam.timer_grace_seconds' => 2,
        'exam.warn_seconds' => 10, 'exam.critical_seconds' => 5, 'exam.tab_grace_seconds' => 1,
        'results.read_minutes' => 30, 'results.pin_tries' => 5, 'results.pin_lock_minutes' => 15, 'results.pin_tries_per_day' => 20, 'results.code_minutes' => 15,
        'intern.otp_minutes' => 15, 'intern.otp_max_attempts' => 5, 'intern.otp_resend_seconds' => 60, 'intern.min_password_length' => 8,
        'staff.min_password_length' => 10,
    ]);

    foreach (config('settings.items') as $key => $d) {
        expect(Settings::int($key))->toBe($d['default']);
        expect($d['default'])->toBeGreaterThanOrEqual($d['min'])->toBeLessThanOrEqual($d['max']);
    }
});

it('falls back to the standard when a saved value is out of range or damaged', function () {
    Setting::create(['key' => 'exam.max_wrong_pins', 'value' => '9999']);
    Setting::create(['key' => 'checkin.opens_hours_before', 'value' => 'soon']);

    expect(Settings::int('exam.max_wrong_pins'))->toBe(5)->and(Settings::int('checkin.opens_hours_before'))->toBe(2);
    expect(fn () => Settings::int('no.such.setting'))->toThrow(InvalidArgumentException::class);
});

// ---- Who can change them

it('is for the super admin only', function () {
    $this->actingAs(settingsUser())->get(route('admin.settings.edit'))->assertOk()->assertSee('System settings')->assertSee('Check-in opens before a sitting starts');

    foreach (['exam_officer', 'invigilator', 'committee', 'question_author', 'reviewer'] as $role) {
        $user = settingsUser($role);
        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.settings.update'), standardForm(['exam.max_wrong_pins' => 10]))->assertForbidden();
        $this->actingAs($user)->post(route('admin.settings.reset'))->assertForbidden();
    }

    expect(Setting::count())->toBe(0);
});

it('sends signed-out visitors to the staff sign-in', function () {
    $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
});

it('shows the standard value and allowed range beside each setting', function () {
    $this->actingAs(settingsUser())->get(route('admin.settings.edit'))->assertOk()
        ->assertSee('Standard: 5 tries · allowed 3 to 10')
        ->assertSee('Exam day')->assertSee('Candidates checking their results')->assertSee('Intern applications')->assertSee('Staff accounts');
});

// ---- Saving

it('saves a change, stores only what differs from the standard, and says who changed it', function () {
    $admin = settingsUser();
    saveSettings(['checkin.opens_hours_before' => 3, 'exam.max_wrong_pins' => 4], $admin)->assertSessionHasNoErrors()->assertRedirect(route('admin.settings.edit'));

    expect(Setting::pluck('value', 'key')->all())->toBe(['checkin.opens_hours_before' => '3', 'exam.max_wrong_pins' => '4'])
        ->and(Setting::find('exam.max_wrong_pins')->updated_by)->toBe($admin->id);

    $this->actingAs($admin)->get(route('admin.settings.edit'))->assertSee('Changed')->assertSee('Chief Admin');
});

it('removes a saved row when the value goes back to the standard', function () {
    saveSettings(['exam.max_wrong_pins' => 4]);
    expect(Setting::count())->toBe(1);

    saveSettings(['exam.max_wrong_pins' => 5]);

    expect(Setting::count())->toBe(0)->and(Settings::int('exam.max_wrong_pins'))->toBe(5);
});

it('says so when nothing changed, and writes nothing to the audit log', function () {
    saveSettings()->assertSessionHas('status', 'Nothing changed.');

    expect(AuditLog::where('action', 'admin.settings.update')->count())->toBe(0);
});

it('records every change in the audit log with the old and new value', function () {
    $admin = settingsUser();
    saveSettings(['exam.max_wrong_pins' => 4, 'results.read_minutes' => 20], $admin);

    $log = AuditLog::where('action', 'admin.settings.update')->firstOrFail();
    expect($log->actor_id)->toBe($admin->id)->and($log->category)->toBe('settings')->and($log->isSensitive())->toBeTrue()
        ->and($log->summary)->toContain('Wrong PINs before an account locks')->toContain('5 → 4')
        ->and($log->meta['changes'][0]['changed']['Wrong PINs before an account locks (tries)'])->toBe(['from' => 5, 'to' => 4])
        ->and($log->meta['changes'][0]['changed']['A candidate can read their results for (minutes)'])->toBe(['from' => 30, 'to' => 20]);

    $this->actingAs(settingsUser('committee'))->get(route('audit.index', ['category' => 'settings']))->assertOk()->assertSee('Changed system settings')->assertSee('System settings');
});

it('refuses values outside the safe range and saves nothing from that form', function () {
    foreach ([
        ['exam.max_wrong_pins', 2],        // too few
        ['exam.max_wrong_pins', 11],       // too many
        ['results.pin_tries', 1],          // would make a lock far too easy
        ['checkin.opens_hours_before', -1],
        ['staff.min_password_length', 4],  // weaker than the floor
        ['exam.max_wrong_pins', 'many'],
        ['exam.max_wrong_pins', ''],
        ['exam.max_wrong_pins', 4.5],
    ] as [$key, $value]) {
        saveSettings(['checkin.opens_hours_before' => 3, $key => $value])->assertSessionHasErrors('s_'.str_replace('.', '__', $key));
    }

    expect(Setting::count())->toBe(0);
});

it('needs the red warning to come after the amber one', function () {
    saveSettings(['exam.warn_seconds' => 10, 'exam.critical_seconds' => 10])->assertSessionHasErrors('s_exam__critical_seconds');
    saveSettings(['exam.warn_seconds' => 8, 'exam.critical_seconds' => 12])->assertSessionHasErrors('s_exam__critical_seconds');
    expect(Setting::count())->toBe(0);

    saveSettings(['exam.warn_seconds' => 15, 'exam.critical_seconds' => 6])->assertSessionHasNoErrors();
    expect(Settings::int('exam.warn_seconds'))->toBe(15)->and(Settings::int('exam.critical_seconds'))->toBe(6);
});

it('puts everything back to the standard values on request', function () {
    saveSettings(['exam.max_wrong_pins' => 4, 'checkin.opens_hours_before' => 3]);
    $admin = settingsUser();

    $this->actingAs($admin)->post(route('admin.settings.reset'))->assertRedirect(route('admin.settings.edit'));

    expect(Setting::count())->toBe(0)->and(Settings::int('exam.max_wrong_pins'))->toBe(5);
    expect(AuditLog::where('action', 'admin.settings.update')->orderByDesc('id')->first()->summary)->toContain('Put system settings back');
});

// ---- The settings actually change how the system behaves

it('changes when check-in opens', function () {
    $registration = waitingCandidate(150); // the sitting starts in 2.5 hours
    $invigilator = settingsUser('invigilator');

    $this->actingAs($invigilator)->post(route('invigilator.check-in', $registration), ['identity_confirmed' => 1]);
    expect($registration->fresh()->status)->toBe('assigned'); // standard: opens 2 hours before, so too early

    saveSettings(['checkin.opens_hours_before' => 3]);
    $this->actingAs($invigilator)->post(route('invigilator.check-in', $registration), ['identity_confirmed' => 1]);
    expect($registration->fresh()->status)->toBe('checked_in');
});

it('changes how long a PIN keeps working after the sitting', function () {
    $registration = waitingCandidate(-30);
    $end = $registration->examSession->ends_at;

    $registration->issuePin();
    expect($registration->fresh()->pin_expires_at->equalTo($end->copy()->addMinutes(60)))->toBeTrue();

    saveSettings(['exam.pin_grace_minutes' => 15]);
    $registration->fresh()->issuePin();
    expect($registration->fresh()->pin_expires_at->equalTo($end->copy()->addMinutes(15)))->toBeTrue();
});

it('changes how many wrong PINs lock an exam account', function () {
    $registration = waitingCandidate(-10);
    $registration->forceFill(['status' => 'admitted', 'admitted_at' => now()])->save();
    $pin = $registration->fresh()->issuePin();
    $wrong = $pin === '000000' ? '111111' : '000000';

    saveSettings(['exam.max_wrong_pins' => 3]);

    foreach (range(1, 2) as $ignored) {
        $this->post(route('candidate.login.store'), ['exam_number' => 'F/1', 'pin' => $wrong]);
    }
    expect($registration->fresh()->locked_at)->toBeNull();

    $this->post(route('candidate.login.store'), ['exam_number' => 'F/1', 'pin' => $wrong]);
    expect($registration->fresh()->locked_at)->not->toBeNull();
});

it('changes how late an answer is still accepted', function () {
    $registration = waitingCandidate(-10);
    $paper = ExamPaper::create(['exam_session_id' => $registration->exam_session_id, 'generated_at' => now(), 'frozen_at' => now()]);
    $attempt = Attempt::create(['exam_registration_id' => $registration->id, 'exam_paper_id' => $paper->id, 'status' => 'in_progress', 'started_at' => now()]);
    $aq = new AttemptQuestion(['attempt_id' => $attempt->id, 'status' => 'pending']);
    $aq->deadline_at = now()->subSeconds(4); // four seconds past the deadline

    $service = app(AttemptService::class);
    expect($service->isOpenForAnswer($aq))->toBeFalse(); // standard grace is 2 seconds

    saveSettings(['exam.timer_grace_seconds' => 5]);
    expect($service->isOpenForAnswer($aq))->toBeTrue();
});

it('hands the clock and tab settings to the exam page', function () {
    $page = file_get_contents(resource_path('views/candidate/exam/question.blade.php'));

    expect($page)->toContain("Settings::int('exam.warn_seconds')")->toContain("Settings::int('exam.critical_seconds')")->toContain("Settings::int('exam.tab_grace_seconds')")
        ->and($page)->toContain('root.dataset.warnSeconds')->toContain('root.dataset.criticalSeconds')->toContain('root.dataset.tabGraceMs')
        ->and($page)->not->toContain("s <= 5 ? 'bad' : s <= 10");
});

it('changes the shortest password for new intern accounts', function () {
    Intake::create(['name' => 'Intake', 'year' => 2026, 'opens_at' => now()->subDay(), 'closes_at' => now()->addWeek()]);
    Mail::fake();
    $form = fn ($email, $password) => ['surname' => 'Eze', 'first_name' => 'Chidi', 'email' => $email, 'phone' => '0801'.random_int(1000000, 9999999), 'password' => $password, 'password_confirmation' => $password];

    $this->post(route('intern.register.store'), $form('a@example.com', 'Passw0rd1'))->assertSessionHasNoErrors(); // 9 characters: fine as standard
    auth('applicant')->logout();

    saveSettings(['intern.min_password_length' => 12]);
    $this->post(route('intern.register.store'), $form('b@example.com', 'Passw0rd1'))->assertSessionHasErrors('password');
    $this->post(route('intern.register.store'), $form('c@example.com', 'Passw0rd1234'))->assertSessionHasNoErrors();
});

it('changes how long an intern\'s emailed code lasts', function () {
    Mail::fake();
    $intake = Intake::create(['name' => 'Intake', 'year' => 2026, 'opens_at' => now()->subDay(), 'closes_at' => now()->addWeek()]);
    $a = Applicant::create(['intake_id' => $intake->id, 'application_number' => 'INT/2026/0001', 'surname' => 'Eze', 'first_name' => 'Chidi', 'email' => 'c@example.com', 'phone' => '08011112222', 'password' => 'Password123', 'status' => 'draft']);

    saveSettings(['intern.otp_minutes' => 30]);
    $a->sendEmailOtp();

    expect($a->fresh()->email_otp_expires_at->equalTo($a->fresh()->email_otp_sent_at->copy()->addMinutes(30)))->toBeTrue();
});

it('changes the shortest staff password', function () {
    $user = settingsUser('exam_officer');
    $user->update(['password' => 'CurrentPass1!']);
    $change = fn ($new) => $this->actingAs($user)->put(route('account.password.update'), ['current_password' => 'CurrentPass1!', 'password' => $new, 'password_confirmation' => $new]);

    $change('Abcdefg12')->assertSessionHasErrors('password');   // 9 characters, standard minimum is 10
    saveSettings(['staff.min_password_length' => 8]);
    $change('Abcdefg12')->assertSessionHasNoErrors();
});

it('shows the new rules to the next request straight away', function () {
    saveSettings(['exam.max_wrong_pins' => 7]);

    expect(Settings::int('exam.max_wrong_pins'))->toBe(7);
    $this->actingAs(settingsUser())->get(route('admin.settings.edit'))->assertSee('value="7"', false);
});
