<?php

use App\Mail\ApplicantOtpMail;
use App\Models\Applicant;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\Intake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function openIntake(array $overrides = []): Intake
{
    return Intake::create($overrides + [
        'name' => 'Internship Intake 2027', 'year' => 2027,
        'opens_at' => now()->subDay(), 'closes_at' => now()->addWeek(),
    ]);
}

function discipline(string $name = 'Pharmacy', string $code = 'PHARM'): ExamCategory
{
    return ExamCategory::create(['kind' => 'intern_discipline', 'name' => $name, 'code' => $code]);
}

function internExam(ExamCategory $category, int $year = 2027): Exam
{
    return Exam::create([
        'exam_category_id' => $category->id, 'title' => $category->name.' Entrance Exam',
        'cycle_year' => $year, 'paper_mode' => 'pool_draw', 'pass_mark' => 50,
    ]);
}

function applicant(Intake $intake, array $overrides = []): Applicant
{
    static $n = 0;
    $n++;

    return Applicant::create($overrides + [
        'intake_id' => $intake->id,
        'application_number' => Applicant::nextApplicationNumber($intake),
        'surname' => 'Okafor', 'first_name' => 'Ada'.$n,
        'email' => "ada{$n}@example.com", 'phone' => '0801000'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'password' => 'Password123', 'status' => 'draft', 'email_verified_at' => now(),
    ]);
}

/** A real 1x1 PNG, so the tests do not need the GD extension. */
function pngFile(string $name = 'me.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
    ));
}

function officer(string $role = 'exam_officer'): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true, 'must_change_password' => false])->save();

    return $user;
}

function submittedApplicant(Intake $intake, ExamCategory $category): Applicant
{
    return applicant($intake, [
        'exam_category_id' => $category->id, 'status' => 'submitted', 'submitted_at' => now(),
        'passport_path' => 'applicants/x/passport.jpg', 'credential_path' => 'applicants/x/credential.pdf',
    ]);
}

// ---- Registration and email code ----

it('does not let anyone register when no window is open', function () {
    openIntake(['opens_at' => now()->addDay(), 'closes_at' => now()->addWeek()]);

    $this->post(route('intern.register.store'), [
        'surname' => 'Okafor', 'first_name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '08012345678',
        'password' => 'Password123', 'password_confirmation' => 'Password123',
    ])->assertSessionHasErrors('email');

    expect(Applicant::count())->toBe(0);
});

it('registers an applicant, issues an application number and emails a code', function () {
    Mail::fake();
    openIntake();

    $this->post(route('intern.register.store'), [
        'surname' => 'Okafor', 'first_name' => 'Ada', 'email' => 'ADA@Example.com', 'phone' => '0801 234 5678',
        'password' => 'Password123', 'password_confirmation' => 'Password123',
    ])->assertRedirect(route('intern.verify'));

    $applicant = Applicant::firstOrFail();
    expect($applicant->application_number)->toBe('INT/2027/0001')
        ->and($applicant->email)->toBe('ada@example.com')
        ->and($applicant->phone)->toBe('08012345678')
        ->and($applicant->status)->toBe('draft')
        ->and($applicant->hasVerifiedEmail())->toBeFalse();

    Mail::assertSent(ApplicantOtpMail::class, fn ($m) => $m->hasTo('ada@example.com'));
    $this->assertAuthenticatedAs($applicant, 'applicant');
});

it('blocks duplicate email and phone at registration', function () {
    $intake = openIntake();
    applicant($intake, ['email' => 'ada@example.com', 'phone' => '08012345678']);

    $this->post(route('intern.register.store'), [
        'surname' => 'Other', 'first_name' => 'Person', 'email' => 'ada@example.com', 'phone' => '08099999999',
        'password' => 'Password123', 'password_confirmation' => 'Password123',
    ])->assertSessionHasErrors('email');

    $this->post(route('intern.register.store'), [
        'surname' => 'Other', 'first_name' => 'Person', 'email' => 'new@example.com', 'phone' => '0801-234-5678',
        'password' => 'Password123', 'password_confirmation' => 'Password123',
    ])->assertSessionHasErrors('phone');

    expect(Applicant::count())->toBe(1);
});

it('keeps unverified applicants on the verification page', function () {
    $intake = openIntake();
    $a = applicant($intake, ['email_verified_at' => null]);

    $this->actingAs($a, 'applicant')->get(route('intern.dashboard'))->assertRedirect(route('intern.verify'));
    $this->actingAs($a, 'applicant')->get(route('intern.profile.edit'))->assertRedirect(route('intern.verify'));
});

it('verifies the email with the right code', function () {
    Mail::fake();
    $a = applicant(openIntake(), ['email_verified_at' => null]);
    $a->sendEmailOtp();
    $code = Mail::sent(ApplicantOtpMail::class)->first()->code;

    $this->actingAs($a, 'applicant')->post(route('intern.verify.store'), ['code' => $code])
        ->assertRedirect(route('intern.dashboard'));

    expect($a->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects a wrong code and locks the code after five wrong tries', function () {
    Mail::fake();
    $a = applicant(openIntake(), ['email_verified_at' => null]);
    $a->sendEmailOtp();
    $code = Mail::sent(ApplicantOtpMail::class)->first()->code;
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $ignored) {
        $this->actingAs($a, 'applicant')->post(route('intern.verify.store'), ['code' => $wrong])->assertSessionHasErrors('code');
    }

    // Even the right code no longer works until a new one is requested
    $this->actingAs($a, 'applicant')->post(route('intern.verify.store'), ['code' => $code])->assertSessionHasErrors('code');
    expect($a->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects an expired code', function () {
    Mail::fake();
    $a = applicant(openIntake(), ['email_verified_at' => null]);
    $a->sendEmailOtp();
    $code = Mail::sent(ApplicantOtpMail::class)->first()->code;

    $this->travel(Applicant::OTP_MINUTES + 1)->minutes();

    $this->actingAs($a, 'applicant')->post(route('intern.verify.store'), ['code' => $code])->assertSessionHasErrors('code');
});

it('limits how often a new code can be requested', function () {
    Mail::fake();
    $a = applicant(openIntake(), ['email_verified_at' => null]);
    $a->sendEmailOtp();

    $this->actingAs($a, 'applicant')->post(route('intern.verify.resend'))->assertSessionHas('error');
    Mail::assertSentCount(1);

    $this->travel(61)->seconds();
    $this->actingAs($a, 'applicant')->post(route('intern.verify.resend'))->assertSessionHas('status');
    Mail::assertSentCount(2);
});

// ---- Sign-in ----

it('signs in with email and password and rejects a wrong password', function () {
    $a = applicant(openIntake(), ['email' => 'ada@example.com']);

    $this->post(route('intern.login.store'), ['email' => 'ada@example.com', 'password' => 'nope'])->assertSessionHasErrors('email');
    $this->assertGuest('applicant');

    $this->post(route('intern.login.store'), ['email' => 'ADA@example.com', 'password' => 'Password123'])
        ->assertRedirect(route('intern.dashboard'));
    $this->assertAuthenticatedAs($a, 'applicant');
});

it('sends signed-out visitors to the right sign-in page', function () {
    $this->get(route('intern.dashboard'))->assertRedirect(route('intern.login'));
    $this->get(route('officer.intakes.index'))->assertRedirect(route('login'));
});

it('does not let applicants into staff pages', function () {
    $a = applicant(openIntake());

    $this->actingAs($a, 'applicant')->get(route('officer.applicants.index'))->assertRedirect(route('login'));
});

// ---- Profile and submission ----

it('saves the profile and stores documents privately', function () {
    Storage::fake('local');
    $intake = openIntake();
    $category = discipline();
    $a = applicant($intake);

    $this->actingAs($a, 'applicant')->put(route('intern.profile.update'), [
        'surname' => 'Okafor', 'first_name' => 'Ada', 'phone' => $a->phone, 'exam_category_id' => $category->id,
        'professional_reg_no' => ' ph/123 ',
        'passport' => pngFile('me.png'),
        'credential' => UploadedFile::fake()->create('degree.pdf', 200, 'application/pdf'),
    ])->assertRedirect(route('intern.profile.edit'));

    $a->refresh();
    expect($a->exam_category_id)->toBe($category->id)
        ->and($a->professional_reg_no)->toBe('PH/123')
        ->and($a->profileIsComplete())->toBeTrue();
    Storage::disk('local')->assertExists([$a->passport_path, $a->credential_path]);

    $this->actingAs($a, 'applicant')->get(route('intern.documents.show', 'passport'))->assertOk();
});

it('will not accept a document that is not an image or pdf', function () {
    Storage::fake('local');
    $a = applicant(openIntake());

    $this->actingAs($a, 'applicant')->put(route('intern.profile.update'), [
        'surname' => 'Okafor', 'first_name' => 'Ada', 'phone' => $a->phone, 'exam_category_id' => discipline()->id,
        'credential' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
    ])->assertSessionHasErrors('credential');
});

it('blocks a phone or registration number another applicant already uses', function () {
    $intake = openIntake();
    applicant($intake, ['phone' => '08055555555', 'professional_reg_no' => 'PH/1']);
    $a = applicant($intake);

    $this->actingAs($a, 'applicant')->put(route('intern.profile.update'), [
        'surname' => 'Okafor', 'first_name' => 'Ada', 'phone' => '0805-555-5555', 'professional_reg_no' => 'ph/1',
        'exam_category_id' => discipline()->id,
    ])->assertSessionHasErrors(['phone', 'professional_reg_no']);
});

it('will not submit an incomplete application', function () {
    $a = applicant(openIntake());

    $this->actingAs($a, 'applicant')->put(route('intern.profile.submit'), [
        'surname' => 'Okafor', 'first_name' => 'Ada', 'phone' => $a->phone, 'exam_category_id' => discipline()->id,
    ])->assertRedirect(route('intern.profile.edit'))->assertSessionHas('error');

    expect($a->fresh()->status)->toBe('draft');
});

it('submits a complete application', function () {
    Storage::fake('local');
    $a = applicant(openIntake());

    $this->actingAs($a, 'applicant')->put(route('intern.profile.submit'), [
        'surname' => 'Okafor', 'first_name' => 'Ada', 'phone' => $a->phone, 'exam_category_id' => discipline()->id,
        'passport' => pngFile(),
        'credential' => UploadedFile::fake()->create('degree.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('intern.dashboard'));

    expect($a->fresh()->status)->toBe('submitted')->and($a->fresh()->submitted_at)->not->toBeNull();
});

it('locks the application once it is submitted or the window has closed', function () {
    $open = openIntake();
    $submitted = applicant($open, ['status' => 'submitted']);
    $this->actingAs($submitted, 'applicant')->get(route('intern.profile.edit'))->assertRedirect(route('intern.dashboard'));
    $this->actingAs($submitted, 'applicant')->put(route('intern.profile.update'), [])->assertForbidden();

    $closed = openIntake(['name' => 'Old', 'year' => 2026, 'opens_at' => now()->subMonth(), 'closes_at' => now()->subDay()]);
    $late = applicant($closed);
    $this->actingAs($late, 'applicant')->put(route('intern.profile.update'), [])->assertForbidden();
});

// ---- Officer review ----

it('approves an applicant, locks the discipline and adds them to the exam list', function () {
    $intake = openIntake();
    $category = discipline();
    $exam = internExam($category);
    $a = submittedApplicant($intake, $category);
    $staff = officer();

    $this->actingAs($staff)->post(route('officer.applicants.approve', $a))->assertRedirect(route('officer.applicants.show', $a));

    $a->refresh();
    expect($a->status)->toBe('approved')
        ->and($a->discipline_locked_at)->not->toBeNull()
        ->and($a->documents_verified_by)->toBe($staff->id);

    $registration = ExamRegistration::where('candidate_type', 'applicant')->where('candidate_id', $a->id)->first();
    expect($registration)->not->toBeNull()->and($registration->exam_id)->toBe($exam->id)->and($registration->status)->toBe('approved');
    $this->assertDatabaseHas('registration_logs', ['exam_id' => $exam->id, 'action' => 'applicant_approved', 'user_id' => $staff->id]);

    $this->actingAs($a, 'applicant')->get(route('intern.profile.edit'))->assertRedirect(route('intern.dashboard'));
});

it('will not approve when no exam exists for the discipline and year', function () {
    $intake = openIntake();
    $category = discipline();
    internExam($category, 2026); // wrong year
    $a = submittedApplicant($intake, $category);

    $this->actingAs(officer())->post(route('officer.applicants.approve', $a))->assertSessionHas('error');

    expect($a->fresh()->status)->toBe('submitted')->and(ExamRegistration::count())->toBe(0);
});

it('approving twice does not create a second registration', function () {
    $intake = openIntake();
    $category = discipline();
    internExam($category);
    $a = submittedApplicant($intake, $category);
    $staff = officer();

    $this->actingAs($staff)->post(route('officer.applicants.approve', $a));
    $this->actingAs($staff)->post(route('officer.applicants.approve', $a))->assertSessionHas('error');

    expect(ExamRegistration::count())->toBe(1);
});

it('needs a reason to reject or return, and shows it to the applicant', function () {
    $intake = openIntake();
    $category = discipline();
    $a = submittedApplicant($intake, $category);
    $staff = officer();

    $this->actingAs($staff)->post(route('officer.applicants.reject', $a), ['note' => ''])->assertSessionHasErrors('note');

    $this->actingAs($staff)->post(route('officer.applicants.return', $a), ['note' => 'Passport is blurry.'])->assertSessionHas('status');
    expect($a->fresh()->status)->toBe('returned');

    $this->actingAs($a->fresh(), 'applicant')->get(route('intern.dashboard'))->assertSee('Passport is blurry.');
    $this->actingAs($a->fresh(), 'applicant')->get(route('intern.profile.edit'))->assertOk();
});

it('cannot return an application after the window has closed', function () {
    $closed = openIntake(['opens_at' => now()->subMonth(), 'closes_at' => now()->subDay()]);
    $a = submittedApplicant($closed, discipline());

    $this->actingAs(officer())->post(route('officer.applicants.return', $a), ['note' => 'Fix it.'])->assertSessionHas('error');

    expect($a->fresh()->status)->toBe('submitted');
});

it('rejects an application with a reason', function () {
    $a = submittedApplicant(openIntake(), discipline());

    $this->actingAs(officer())->post(route('officer.applicants.reject', $a), ['note' => 'Not a recognised qualification.']);

    expect($a->fresh()->status)->toBe('rejected')->and($a->fresh()->review_note)->toBe('Not a recognised qualification.');
});

it('keeps applicant review to exam officers and super admins', function () {
    $a = submittedApplicant(openIntake(), discipline());

    foreach (['invigilator', 'question_author', 'committee'] as $role) {
        $this->actingAs(officer($role))->get(route('officer.applicants.index'))->assertForbidden();
        $this->actingAs(officer($role))->post(route('officer.applicants.approve', $a))->assertForbidden();
    }

    $this->actingAs(officer('super_admin'))->get(route('officer.applicants.index'))->assertOk();
});

it('lets an exam officer manage registration windows', function () {
    $staff = officer();

    $this->actingAs($staff)->post(route('officer.intakes.store'), [
        'name' => 'Internship Intake 2028', 'year' => 2028, 'opens_at' => '2028-01-10T08:00', 'closes_at' => '2028-02-10T17:00',
    ])->assertRedirect(route('officer.intakes.index'));

    $intake = Intake::firstOrFail();
    expect($intake->created_by)->toBe($staff->id);

    $this->actingAs($staff)->put(route('officer.intakes.update', $intake), [
        'name' => $intake->name, 'year' => 2028, 'opens_at' => '2028-02-10T08:00', 'closes_at' => '2028-02-01T17:00',
    ])->assertSessionHasErrors('closes_at');
});

it('lists applicants for review', function () {
    $a = submittedApplicant(openIntake(), discipline());

    $this->actingAs(officer())->get(route('officer.applicants.index'))->assertOk()->assertSee($a->application_number);
    $this->actingAs(officer())->get(route('officer.applicants.show', $a))->assertOk()->assertSee($a->email);
});

// ---- After approval ----

it('shows the slip once a sitting is assigned, without a PIN', function () {
    $intake = openIntake();
    $category = discipline();
    $exam = internExam($category);
    $a = submittedApplicant($intake, $category);
    $a->forceFill(['status' => 'approved'])->save();

    $this->actingAs($a, 'applicant')->get(route('intern.slip'))->assertNotFound();

    $sitting = ExamSession::create([
        'exam_id' => $exam->id, 'name' => 'Pharmacy A', 'venue' => 'ICT Lab',
        'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(2),
    ]);
    ExamRegistration::create([
        'exam_id' => $exam->id, 'exam_session_id' => $sitting->id, 'candidate_type' => 'applicant',
        'candidate_id' => $a->id, 'seat_number' => '7', 'status' => 'approved',
    ]);

    $this->actingAs($a, 'applicant')->get(route('intern.slip'))
        ->assertOk()->assertSee($a->application_number)->assertSee('ICT Lab')->assertDontSee('Your PIN');
});

it('lets an approved intern sign in on exam day with the application number and PIN', function () {
    $intake = openIntake();
    $category = discipline();
    $exam = internExam($category);
    $a = submittedApplicant($intake, $category);
    $sitting = ExamSession::create([
        'exam_id' => $exam->id, 'name' => 'Pharmacy A', 'status' => 'open',
        'starts_at' => now()->subHour(), 'ends_at' => now()->addHours(2),
    ]);
    $registration = ExamRegistration::create([
        'exam_id' => $exam->id, 'exam_session_id' => $sitting->id, 'candidate_type' => 'applicant',
        'candidate_id' => $a->id, 'status' => 'approved', 'admitted_at' => now(),
    ]);
    $pin = $registration->fresh()->issuePin();

    $this->post(route('candidate.login.store'), ['exam_number' => strtolower($a->application_number), 'pin' => $pin])
        ->assertRedirect(route('candidate.dashboard'));
});
