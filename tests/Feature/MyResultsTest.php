<?php

use App\Mail\ResultsCodeMail;
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
use App\Models\SectionResult;
use App\Models\StaffCandidate;
use App\Models\StaffResultCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

/**
 * A staff candidate who sat an exam and has a result. Release is switched on by default.
 *
 * @return array{exam: Exam, candidate: StaffCandidate, registration: ExamRegistration, result: Result}
 */
function sat(array $o = []): array
{
    static $n = 0;
    $n++;

    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => "Cadre {$n}", 'code' => "C{$n}"]);
    $exam = Exam::create([
        'exam_category_id' => $category->id, 'title' => $o['title'] ?? "Promotion Exam {$n}", 'cycle_year' => 2026, 'paper_mode' => 'fixed_set',
        'pass_mark' => 50, 'pass_mark_type' => 'percentage', 'result_release' => $o['mode'] ?? 'full_breakdown',
        'results_released_at' => array_key_exists('released', $o) ? $o['released'] : now()->subDay(), 'top_n' => $o['top_n'] ?? null,
    ]);
    $section = ExamSection::create(['exam_id' => $exam->id, 'position' => 1, 'title' => 'General Knowledge', 'question_count' => 10, 'seconds_per_question' => 60, 'marks_per_question' => 1]);
    $sitting = ExamSession::create(['exam_id' => $exam->id, 'name' => 'Session 1', 'capacity' => 10, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHours(2)]);
    $paper = ExamPaper::create(['exam_session_id' => $sitting->id, 'generated_at' => now()->subDays(3), 'frozen_at' => now()->subDays(3)]);

    // The same person can sit more than one exam, so reuse the candidate when the file number is already there
    $candidate = StaffCandidate::firstOrCreate(['file_number' => $o['file'] ?? "F/00{$n}"], [
        'surname' => $o['surname'] ?? 'Okoro', 'first_name' => $o['first'] ?? 'Ada',
        'cadre' => 'Nurse', 'level' => 'I', 'email' => array_key_exists('email', $o) ? $o['email'] : "ada{$n}@example.com",
    ]);
    $registration = ExamRegistration::create(['exam_id' => $exam->id, 'exam_session_id' => $sitting->id, 'candidate_type' => 'staff', 'candidate_id' => $candidate->id, 'seat_number' => '1', 'status' => 'completed']);
    $attempt = Attempt::create(['exam_registration_id' => $registration->id, 'exam_paper_id' => $paper->id, 'status' => 'submitted', 'started_at' => now()->subDays(2)]);

    $passed = $o['passed'] ?? true;
    $result = Result::create([
        'attempt_id' => $attempt->id, 'exam_registration_id' => $registration->id, 'exam_id' => $exam->id,
        'total_marks' => $passed ? 8 : 3, 'max_marks' => 10, 'percentage' => $passed ? 80 : 30, 'passed' => $passed, 'rank_position' => 1, 'shortlisted' => $o['shortlisted'] ?? false,
    ]);
    SectionResult::create(['result_id' => $result->id, 'exam_section_id' => $section->id, 'marks' => $passed ? 8 : 3, 'max_marks' => 10, 'correct_count' => 8, 'wrong_count' => 2, 'unanswered_count' => 0]);

    return compact('exam', 'candidate', 'registration', 'result');
}

/** Ask for a code and return it, as the candidate would read it in the email. */
function codeFor(string $file): string
{
    Mail::fake();
    test()->post(route('myresults.send'), ['file_number' => $file])->assertRedirect(route('myresults.verify'));

    $sent = Mail::sent(ResultsCodeMail::class);
    expect($sent)->toHaveCount(1);

    return $sent->first()->code;
}

beforeEach(fn () => RateLimiter::clear('myresults-send-ip:127.0.0.1'));

// ---- Getting in

it('emails a code to the address on record and shows the released result once it is entered', function () {
    $f = sat(['file' => 'F/100', 'email' => 'ada@example.com']);

    $code = codeFor('F/100');
    Mail::assertSent(ResultsCodeMail::class, fn ($m) => $m->hasTo('ada@example.com'));

    $this->post(route('myresults.verify.store'), ['code' => $code])->assertRedirect(route('myresults.show'));

    $this->get(route('myresults.show'))->assertOk()
        ->assertSee($f['exam']->title)->assertSee('Passed')->assertSee('General Knowledge')->assertSee('8 ')->assertSee('80%')
        ->assertDontSee('rank');
});

it('answers the same way whether or not the file number, email or release exists', function () {
    Mail::fake();
    sat(['file' => 'F/200', 'email' => null]);           // no email on record
    sat(['file' => 'F/201', 'released' => null]);        // not released yet
    sat(['file' => 'F/202', 'mode' => 'hidden']);        // set to show candidates nothing

    $responses = collect(['F/200', 'F/201', 'F/202', 'F/999'])->map(fn ($file) => $this->post(route('myresults.send'), ['file_number' => $file]));

    $responses->each(fn ($r) => $r->assertRedirect(route('myresults.verify'))->assertSessionHas('status', fn ($s) => str_contains($s, 'If results are available')));
    Mail::assertNothingSent();
    expect(StaffResultCode::count())->toBe(0);
});

it('treats the file number the way staff type it', function () {
    sat(['file' => 'F/300']);
    $code = codeFor('  f/300 ');

    $this->post(route('myresults.verify.store'), ['code' => $code])->assertRedirect(route('myresults.show'));
});

// ---- What is shown

it('shows only pass or fail when the exam is set that way', function () {
    $f = sat(['file' => 'F/400', 'mode' => 'pass_fail']);
    $code = codeFor('F/400');
    $this->post(route('myresults.verify.store'), ['code' => $code]);

    $this->get(route('myresults.show'))->assertOk()->assertSee($f['exam']->title)->assertSee('Passed')
        ->assertDontSee('General Knowledge')->assertDontSee('80%')->assertDontSee('Pass mark');
});

it('says a candidate did not pass in words, not only colour', function () {
    sat(['file' => 'F/410', 'passed' => false]);
    $code = codeFor('F/410');
    $this->post(route('myresults.verify.store'), ['code' => $code]);

    $this->get(route('myresults.show'))->assertSee('Did not pass')->assertDontSee('Shortlisted');
});

it('shows shortlisted when the exam has a shortlist and the candidate is on it', function () {
    sat(['file' => 'F/420', 'top_n' => 5, 'shortlisted' => true]);
    $code = codeFor('F/420');
    $this->post(route('myresults.verify.store'), ['code' => $code]);

    $this->get(route('myresults.show'))->assertSee('Shortlisted');
});

it('stops showing a result as soon as the release is withdrawn', function () {
    $f = sat(['file' => 'F/430']);
    $code = codeFor('F/430');
    $this->post(route('myresults.verify.store'), ['code' => $code]);
    $this->get(route('myresults.show'))->assertSee($f['exam']->title);

    $f['exam']->update(['results_released_at' => null]);

    $this->get(route('myresults.show'))->assertOk()->assertDontSee($f['exam']->title)->assertSee('No results are available');
});

it('never shows one candidate another candidate\'s result', function () {
    sat(['file' => 'F/500', 'title' => 'Exam For Ada', 'first' => 'Ada']);
    sat(['file' => 'F/501', 'title' => 'Exam For Bola', 'first' => 'Bola', 'surname' => 'Danjuma']);

    $code = codeFor('F/500');
    $this->post(route('myresults.verify.store'), ['code' => $code]);

    $this->get(route('myresults.show'))->assertSee('Exam For Ada')->assertDontSee('Exam For Bola')->assertDontSee('Danjuma');
});

it('forbids caching of the results page', function () {
    sat(['file' => 'F/510']);
    $code = codeFor('F/510');
    $this->post(route('myresults.verify.store'), ['code' => $code]);

    expect($this->get(route('myresults.show'))->headers->get('Cache-Control'))->toContain('no-store');
});

// ---- Keeping the code safe

it('rejects wrong codes and locks the code after five', function () {
    sat(['file' => 'F/600']);
    $code = codeFor('F/600');
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $ignored) {
        $this->post(route('myresults.verify.store'), ['code' => $wrong])->assertSessionHasErrors('code');
    }

    $this->post(route('myresults.verify.store'), ['code' => $code])->assertSessionHasErrors('code');
    $this->get(route('myresults.show'))->assertRedirect(route('myresults.request'));
    expect(AuditLog::where('action', 'candidate.results_code_failed')->count())->toBe(1);
});

it('rejects an expired code', function () {
    sat(['file' => 'F/610']);
    $code = codeFor('F/610');

    $this->travel(16)->minutes();

    $this->post(route('myresults.verify.store'), ['code' => $code])->assertSessionHasErrors('code');
});

it('lets a code be used only once', function () {
    sat(['file' => 'F/620']);
    $code = codeFor('F/620');

    $this->post(route('myresults.verify.store'), ['code' => $code])->assertRedirect(route('myresults.show'));
    $this->post(route('myresults.logout'));

    // same session cannot go back to the code page without asking again, and the old code is spent
    $this->withSession(['my_results_file' => 'F/620'])->post(route('myresults.verify.store'), ['code' => $code])->assertSessionHasErrors('code');
});

it('replaces an older code when a new one is requested', function () {
    sat(['file' => 'F/630']);
    $first = codeFor('F/630');
    $second = codeFor('F/630');

    expect(StaffResultCode::count())->toBe(1);
    $this->post(route('myresults.verify.store'), ['code' => $first === $second ? '123456' : $first])->assertSessionHasErrors('code');
    $this->post(route('myresults.verify.store'), ['code' => $second])->assertRedirect(route('myresults.show'));
});

it('stores the code only as a hash', function () {
    sat(['file' => 'F/640']);
    $code = codeFor('F/640');

    $stored = StaffResultCode::firstOrFail();
    expect($stored->code_hash)->not->toContain($code)->and(strlen($stored->code_hash))->toBeGreaterThan(30);
});

it('ends the reading session after thirty minutes and on sign-out', function () {
    sat(['file' => 'F/650']);
    $code = codeFor('F/650');
    $this->post(route('myresults.verify.store'), ['code' => $code]);
    $this->get(route('myresults.show'))->assertOk();

    $this->travel(31)->minutes();
    $this->get(route('myresults.show'))->assertRedirect(route('myresults.request'));
});

it('signs out on request', function () {
    sat(['file' => 'F/660']);
    $code = codeFor('F/660');
    $this->post(route('myresults.verify.store'), ['code' => $code]);

    $this->post(route('myresults.logout'))->assertRedirect(route('myresults.request'));
    $this->get(route('myresults.show'))->assertRedirect(route('myresults.request'));
});

it('limits how many codes can be requested', function () {
    sat(['file' => 'F/670']);
    Mail::fake();

    foreach (range(1, 3) as $ignored) {
        $this->post(route('myresults.send'), ['file_number' => 'F/670'])->assertRedirect(route('myresults.verify'));
    }

    $this->post(route('myresults.send'), ['file_number' => 'F/670'])->assertSessionHasErrors('file_number');
    Mail::assertSent(ResultsCodeMail::class, 3);
});

it('sends people with no code request or session back to the start', function () {
    $this->get(route('myresults.verify'))->assertRedirect(route('myresults.email'));
    $this->get(route('myresults.show'))->assertRedirect(route('myresults.request'));
    $this->post(route('myresults.verify.store'), ['code' => '123456'])->assertRedirect(route('myresults.email'));
});

// ---- The trail

it('records asking for a code and viewing results in the audit log', function () {
    sat(['file' => 'F/700']);
    $code = codeFor('F/700');
    $this->post(route('myresults.verify.store'), ['code' => $code]);
    $this->get(route('myresults.show'));

    expect(AuditLog::where('action', 'candidate.results_code_sent')->firstOrFail()->subject_label)->toBe('F/700')
        ->and(AuditLog::where('action', 'candidate.results_viewed')->count())->toBe(1)
        ->and(AuditLog::where('action', 'like', 'candidate.results%')->pluck('category')->unique()->all())->toBe(['results']);

    $meta = json_encode(AuditLog::pluck('meta')->all());
    expect($meta)->not->toContain($code);
});

// ---- Staff in the exam office

it('tells the exam office which staff candidates cannot get a code', function () {
    sat(['file' => 'F/800', 'email' => null]);
    $f = sat(['file' => 'F/801']);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'super_admin', 'is_active' => true, 'must_change_password' => false])->save();

    $this->actingAs($admin)->get(route('results.exams.show', Exam::where('title', 'like', 'Promotion Exam%')->orderBy('id')->first()))
        ->assertOk()->assertSee('no email address on file');

    $this->actingAs($admin)->get(route('results.exams.show', $f['exam']))->assertOk()->assertDontSee('no email address on file');
});

it('is reachable from the front door', function () {
    $this->get('/')->assertOk()->assertSee(route('myresults.request'), false)->assertSee('I want to see my result');
    $this->get(route('myresults.request'))->assertOk()->assertSee('Show my results')->assertSee('Lost your PIN?');
    $this->get(route('myresults.email'))->assertOk()->assertSee('Email me a code');
});

// ---- Interns

function approvedIntern(array $o = []): array
{
    $intake = Intake::create(['name' => 'Intake 2026', 'year' => 2026, 'opens_at' => now()->subMonth(), 'closes_at' => now()->subWeek()]);
    $category = ExamCategory::create(['kind' => 'intern_discipline', 'name' => 'Pharmacy', 'code' => 'PH']);
    $exam = Exam::create([
        'exam_category_id' => $category->id, 'title' => 'Pharmacy Entrance 2026', 'cycle_year' => 2026, 'paper_mode' => 'pool_draw', 'pass_mark' => 50, 'pass_mark_type' => 'percentage',
        'result_release' => $o['mode'] ?? 'full_breakdown', 'results_released_at' => array_key_exists('released', $o) ? $o['released'] : now()->subDay(),
    ]);
    $section = ExamSection::create(['exam_id' => $exam->id, 'position' => 1, 'title' => 'Pharmacy', 'question_count' => 10, 'seconds_per_question' => 60, 'marks_per_question' => 1]);
    $sitting = ExamSession::create(['exam_id' => $exam->id, 'name' => 'Pharmacy A', 'capacity' => 10, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHours(2)]);
    $paper = ExamPaper::create(['exam_session_id' => $sitting->id, 'generated_at' => now()->subDays(3), 'frozen_at' => now()->subDays(3)]);

    $applicant = Applicant::create([
        'intake_id' => $intake->id, 'exam_category_id' => $category->id, 'application_number' => 'INT/2026/0001', 'surname' => 'Danjuma', 'first_name' => 'Bola',
        'email' => 'bola@example.com', 'phone' => '08011112222', 'password' => 'Password123', 'status' => 'approved', 'email_verified_at' => now(),
    ]);
    $registration = ExamRegistration::create(['exam_id' => $exam->id, 'exam_session_id' => $sitting->id, 'candidate_type' => 'applicant', 'candidate_id' => $applicant->id, 'seat_number' => '2', 'status' => $o['status'] ?? 'completed']);

    if (($o['status'] ?? 'completed') === 'completed') {
        $attempt = Attempt::create(['exam_registration_id' => $registration->id, 'exam_paper_id' => $paper->id, 'status' => 'submitted', 'started_at' => now()->subDays(2)]);
        $result = Result::create(['attempt_id' => $attempt->id, 'exam_registration_id' => $registration->id, 'exam_id' => $exam->id, 'total_marks' => 9, 'max_marks' => 10, 'percentage' => 90, 'passed' => true, 'rank_position' => 1, 'shortlisted' => true]);
        SectionResult::create(['result_id' => $result->id, 'exam_section_id' => $section->id, 'marks' => 9, 'max_marks' => 10]);
    }

    return compact('applicant', 'exam');
}

it('shows an intern their released result on their dashboard', function () {
    $f = approvedIntern();

    $this->actingAs($f['applicant'], 'applicant')->get(route('intern.dashboard'))->assertOk()
        ->assertSee('Pharmacy Entrance 2026')->assertSee('Passed')->assertSee('90%')->assertSee('Result');
});

it('tells an intern the exam is done but the result is not out yet', function () {
    $f = approvedIntern(['released' => null]);

    $this->actingAs($f['applicant'], 'applicant')->get(route('intern.dashboard'))->assertOk()
        ->assertSee('Your result will appear here once the exam office releases it')->assertDontSee('90%');
});

it('shows an intern nothing when the exam shows candidates nothing', function () {
    $f = approvedIntern(['mode' => 'hidden']);

    $this->actingAs($f['applicant'], 'applicant')->get(route('intern.dashboard'))->assertOk()->assertDontSee('90%')->assertDontSee('Passed');
});

it('does not show an intern a result before they have sat the exam', function () {
    $f = approvedIntern(['status' => 'assigned']);

    $this->actingAs($f['applicant'], 'applicant')->get(route('intern.dashboard'))->assertOk()->assertDontSee('Passed')->assertDontSee('has completed');
});


// ---- Number and PIN

/** The PIN a candidate was handed at check-in, long since expired for the exam itself. */
function pinFor(ExamRegistration $registration): string
{
    $registration->examSession->update(['ends_at' => now()->subDay()]); // the sitting is over, so the exam PIN has expired
    $pin = $registration->fresh()->issuePin();
    $registration->examSession->update(['ends_at' => now()->subDay()]);

    return $pin;
}

function pinSignIn(string $number, string $pin)
{
    return test()->post(route('myresults.pin'), ['exam_number' => $number, 'pin' => $pin]);
}

it('shows staff their result when they enter their file number and exam PIN', function () {
    $f = sat(['file' => 'F/900']);
    $pin = pinFor($f['registration']);

    pinSignIn('f/900 ', $pin)->assertRedirect(route('myresults.show'));

    test()->get(route('myresults.show'))->assertOk()->assertSee($f['exam']->title)->assertSee('Passed')->assertSee('General Knowledge')->assertSee('file number F/900');
});

it('works after the exam PIN has expired, but never starts an exam', function () {
    $f = sat(['file' => 'F/901']);
    $pin = pinFor($f['registration']);

    expect($f['registration']->fresh()->pinIsValid($pin))->toBeFalse(); // exam sign-in: expired
    pinSignIn('F/901', $pin)->assertRedirect(route('myresults.show'));    // results: allowed

    $this->post(route('candidate.login.store'), ['exam_number' => 'F/901', 'pin' => $pin])->assertSessionHasErrors();
    $this->assertGuest();
});

it('shows an intern their result with their application number and PIN', function () {
    $f = approvedIntern();
    $registration = ExamRegistration::where('candidate_type', 'applicant')->firstOrFail();
    $pin = pinFor($registration);

    pinSignIn('int/2026/0001', $pin)->assertRedirect(route('myresults.show'));

    test()->get(route('myresults.show'))->assertOk()->assertSee('Pharmacy Entrance 2026')->assertSee('Passed')->assertSee('application INT/2026/0001')->assertSee('Danjuma');
});

it('gives the same answer for a wrong PIN, a wrong number and someone else\'s PIN', function () {
    $ada = sat(['file' => 'F/910']);
    $bola = sat(['file' => 'F/911', 'first' => 'Bola']);
    $adaPin = pinFor($ada['registration']);
    $bolaPin = pinFor($bola['registration']);
    $wrong = $adaPin === '000000' ? '111111' : '000000';

    $messages = collect([
        ['F/910', $wrong],      // right number, wrong PIN
        ['F/999', $adaPin],     // number that does not exist
        ['F/910', $bolaPin],    // right number, a different person's PIN
    ])->map(fn ($try) => pinSignIn(...$try)->assertSessionHasErrors('pin')->assertRedirect()->baseResponse->getSession()->get('errors')->first('pin'));

    expect($messages->unique())->toHaveCount(1)->and($messages->first())->toContain('do not match');
    test()->get(route('myresults.show'))->assertRedirect(route('myresults.request'));
});

it('locks a number for fifteen minutes after five wrong PINs, even for the right PIN', function () {
    $f = sat(['file' => 'F/920']);
    $pin = pinFor($f['registration']);
    $wrong = $pin === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $ignored) {
        pinSignIn('F/920', $wrong)->assertSessionHasErrors('pin');
    }

    $locked = pinSignIn('F/920', $pin)->assertSessionHasErrors('pin');
    expect(session('errors')->first('pin'))->toContain('Too many tries');
    test()->get(route('myresults.show'))->assertRedirect(route('myresults.request'));

    test()->travel(16)->minutes();
    pinSignIn('F/920', $pin)->assertRedirect(route('myresults.show'));
});

it('stops a slow guesser with a daily limit', function () {
    $f = sat(['file' => 'F/930']);
    $pin = pinFor($f['registration']);
    $wrong = $pin === '000000' ? '111111' : '000000';

    foreach (range(1, 4) as $round) {
        foreach (range(1, 5) as $ignored) {
            pinSignIn('F/930', $wrong);
        }
        test()->travel(16)->minutes();
        RateLimiter::clear('myresults-pin-ip:127.0.0.1'); // a different computer each time
    }

    pinSignIn('F/930', $pin)->assertSessionHasErrors('pin');
    expect(session('errors')->first('pin'))->toContain('today');
});

it('limits guessing from one computer across many numbers', function () {
    sat(['file' => 'F/940']);

    foreach (range(1, 15) as $i) {
        pinSignIn('F/9'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'99', '123456');
    }

    pinSignIn('F/940', '123456')->assertSessionHasErrors('pin');
    expect(session('errors')->first('pin'))->toContain('Too many tries');
});

it('shows a candidate only the exam their PIN belongs to', function () {
    $first = sat(['file' => 'F/950', 'title' => 'Promotion 2025']);
    $second = sat(['file' => 'F/950', 'title' => 'Promotion 2026']); // same person, another exam
    $firstPin = pinFor($first['registration']);

    pinSignIn('F/950', $firstPin)->assertRedirect(route('myresults.show'));

    test()->get(route('myresults.show'))->assertSee('Promotion 2025')->assertDontSee('Promotion 2026');
});

it('lets a candidate in but shows nothing while results are not released', function () {
    $f = sat(['file' => 'F/960', 'released' => null]);
    $pin = pinFor($f['registration']);

    pinSignIn('F/960', $pin)->assertRedirect(route('myresults.show'));

    test()->get(route('myresults.show'))->assertOk()->assertSee('No results are available')->assertDontSee($f['exam']->title);
});

it('ends a PIN session after thirty minutes and on sign-out', function () {
    $f = sat(['file' => 'F/970']);
    pinSignIn('F/970', pinFor($f['registration']));
    test()->get(route('myresults.show'))->assertOk();

    test()->post(route('myresults.logout'))->assertRedirect(route('myresults.request'));
    test()->get(route('myresults.show'))->assertRedirect(route('myresults.request'));

    pinSignIn('F/970', pinFor($f['registration']));
    test()->travel(31)->minutes();
    test()->get(route('myresults.show'))->assertRedirect(route('myresults.request'));
});

it('needs a six-digit PIN', function () {
    sat(['file' => 'F/980']);

    pinSignIn('F/980', '12345')->assertSessionHasErrors('pin');
    pinSignIn('F/980', 'abcdef')->assertSessionHasErrors('pin');
    pinSignIn('', '123456')->assertSessionHasErrors('exam_number');
});

it('records PIN lockouts for real numbers only, and which way people got in', function () {
    $f = sat(['file' => 'F/990']);
    $pin = pinFor($f['registration']);
    $wrong = $pin === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $ignored) {
        pinSignIn('F/990', $wrong);
        pinSignIn('F/00000', $wrong); // a number nobody has
    }
    expect(AuditLog::where('action', 'candidate.results_pin_locked')->count())->toBe(1)
        ->and(AuditLog::where('action', 'candidate.results_pin_locked')->first()->subject_label)->toBe('F/990')
        ->and(AuditLog::where('subject_label', 'F/00000')->count())->toBe(0);

    test()->travel(16)->minutes();
    pinSignIn('F/990', $pin);
    test()->get(route('myresults.show'));

    $viewed = AuditLog::where('action', 'candidate.results_viewed')->firstOrFail();
    expect($viewed->meta['via'])->toBe('pin')->and(json_encode(AuditLog::pluck('meta')->all()))->not->toContain($pin);
});

it('still lets staff who lost the PIN use the emailed code, and shows both ways in', function () {
    $f = sat(['file' => 'F/995']);
    $code = codeFor('F/995');

    test()->post(route('myresults.verify.store'), ['code' => $code])->assertRedirect(route('myresults.show'));
    test()->get(route('myresults.show'))->assertOk()->assertSee($f['exam']->title);
    expect(AuditLog::where('action', 'candidate.results_viewed')->firstOrFail()->meta['via'])->toBe('email');
});

// ---- System settings that govern the results pages

it('follows the settings for PIN tries, how long a lock lasts and how long results can be read', function () {
    $f = sat(['file' => 'F/SET']);
    $pin = pinFor($f['registration']);
    $wrong = $pin === '000000' ? '111111' : '000000';

    app(App\Support\Settings::class)->save(['results.pin_tries' => 3, 'results.pin_lock_minutes' => 30, 'results.read_minutes' => 10], null);

    foreach (range(1, 3) as $ignored) {
        pinSignIn('F/SET', $wrong)->assertSessionHasErrors('pin');
    }
    pinSignIn('F/SET', $pin)->assertSessionHasErrors('pin');    // locked after only three

    test()->travel(16)->minutes();
    pinSignIn('F/SET', $pin)->assertSessionHasErrors('pin');    // the lock lasts 30 minutes now, not 15

    test()->travel(15)->minutes();
    pinSignIn('F/SET', $pin)->assertRedirect(route('myresults.show'));

    test()->travel(11)->minutes();                                // readable for 10 minutes now, not 30
    test()->get(route('myresults.show'))->assertRedirect(route('myresults.request'));
});
