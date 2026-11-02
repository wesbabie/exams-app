<?php

use App\Models\Applicant;
use App\Models\AttemptEvent;
use App\Models\AuditLog;
use App\Models\Attempt;
use App\Models\ExamPaper;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\RegistrationLog;
use App\Models\StaffCandidate;
use App\Models\StaffLog;
use App\Models\Topic;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function person(string $role, string $name = null): User
{
    static $n = 0;
    $n++;
    $user = User::factory()->create(['name' => $name ?? "Person {$n}"]);
    $user->forceFill(['role' => $role, 'is_active' => true, 'must_change_password' => false])->save();

    return $user;
}

// ---- Recording what staff do

it('records an edit with who did it and the old and new values', function () {
    $officer = person('exam_officer', 'Ngozi Officer');
    $topic = Topic::create(['name' => 'Old name', 'exam_category_id' => null]);

    $this->actingAs($officer)->put(route('bank.topics.update', $topic), ['name' => 'New name'])->assertSessionHasNoErrors();

    $log = AuditLog::where('action', 'bank.topics.update')->firstOrFail();
    expect($log->actor_id)->toBe($officer->id)
        ->and($log->actor_name)->toBe('Ngozi Officer')
        ->and($log->category)->toBe('questions')
        ->and($log->summary)->toContain('Edited a topic')->toContain('New name')
        ->and($log->meta['changes'][0]['changed']['name'])->toBe(['from' => 'Old name', 'to' => 'New name']);
});

it('says so when a form was saved without changing anything', function () {
    $topic = Topic::create(['name' => 'Same', 'exam_category_id' => null]);

    $this->actingAs(person('exam_officer'))->put(route('bank.topics.update', $topic), ['name' => 'Same'])->assertSessionHasNoErrors();

    expect(AuditLog::where('action', 'bank.topics.update')->firstOrFail()->summary)->toContain('(nothing changed)');
});

it('records a creation and finds the new thing for the subject', function () {
    $this->actingAs(person('exam_officer'))->post(route('officer.categories.store'), [
        'kind' => 'staff_cadre', 'name' => 'Pharmacist II', 'code' => 'PH2',
    ])->assertSessionHasNoErrors();

    $log = AuditLog::where('action', 'officer.categories.store')->firstOrFail();
    expect($log->summary)->toContain('Added an exam category')->and($log->subject_label)->toBe('Pharmacist II');
});

it('does not record an action that was refused', function () {
    $topic = Topic::create(['name' => 'Keep', 'exam_category_id' => null]);

    $this->actingAs(person('exam_officer'))->put(route('bank.topics.update', $topic), ['name' => ''])->assertSessionHasErrors('name');

    expect(AuditLog::where('action', 'bank.topics.update')->count())->toBe(0);
});

it('does not record someone who is not allowed to do it', function () {
    $topic = Topic::create(['name' => 'Keep', 'exam_category_id' => null]);

    $this->actingAs(person('committee'))->put(route('bank.topics.update', $topic), ['name' => 'Hacked'])->assertForbidden();

    expect(AuditLog::where('action', 'bank.topics.update')->count())->toBe(0)->and($topic->fresh()->name)->toBe('Keep');
});

it('never stores passwords or PINs', function () {
    Audit::record('test.secret', 'accounts', 'Test', ['meta' => ['password' => 'hunter2', 'pin' => '123456', 'nested' => ['pin_hash' => 'x', 'ok' => 'fine'], 'note' => str_repeat('x', 500)]]);

    $meta = AuditLog::where('action', 'test.secret')->firstOrFail()->meta;
    expect($meta)->not->toHaveKey('password')->not->toHaveKey('pin')
        ->and($meta['nested'])->toBe(['ok' => 'fine'])
        ->and(mb_strlen($meta['note']))->toBeLessThan(250);
});

it('records staff sign-ins, failed sign-ins and sign-outs', function () {
    $user = person('exam_officer', 'Ada Officer');
    $user->update(['email' => 'ada@example.com', 'password' => 'Password123']);

    $this->post(route('login.store'), ['email' => 'ada@example.com', 'password' => 'wrong'])->assertSessionHasErrors();
    $failed = AuditLog::where('action', 'auth.failed')->firstOrFail();
    expect($failed->meta['email'])->toBe('ada@example.com')->and($failed->actor_id)->toBeNull();

    $this->post(route('login.store'), ['email' => 'ada@example.com', 'password' => 'Password123']);
    expect(AuditLog::where('action', 'auth.login')->where('actor_id', $user->id)->count())->toBe(1);

    $this->post(route('logout'));
    expect(AuditLog::where('action', 'auth.logout')->where('actor_id', $user->id)->count())->toBe(1);
});

it('copies the older logs into the audit trail as they are written', function () {
    $admin = person('super_admin', 'Chief Admin');
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'NO1', 'code' => 'NO1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50]);

    $this->actingAs($admin);
    RegistrationLog::record($exam->id, 'results_released', 'Candidates see: nothing');
    StaffLog::record($admin->id, 'password_reset', 'Chief Admin');

    $release = AuditLog::where('action', 'reg.results_released')->firstOrFail();
    expect($release->category)->toBe('results')->and($release->exam_id)->toBe($exam->id)->and($release->isSensitive())->toBeTrue()->and($release->actor_name)->toBe('Chief Admin');

    $reset = AuditLog::where('action', 'staff.password_reset')->firstOrFail();
    expect($reset->category)->toBe('accounts')->and($reset->isSensitive())->toBeTrue();
});

it('records an account locking after wrong PINs', function () {
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'NO1', 'code' => 'NO1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50]);
    $c = StaffCandidate::create(['file_number' => 'F/001', 'surname' => 'Okoro', 'first_name' => 'Ada', 'cadre' => 'NO', 'level' => 'I']);
    $reg = ExamRegistration::create(['exam_id' => $exam->id, 'candidate_type' => 'staff', 'candidate_id' => $c->id, 'status' => 'admitted']);

    foreach (range(1, 4) as $ignored) {
        expect($reg->recordFailedPin(5))->toBeFalse();
    }
    expect(AuditLog::where('action', 'candidate.locked')->count())->toBe(0);

    expect($reg->recordFailedPin(5))->toBeTrue();
    $log = AuditLog::where('action', 'candidate.locked')->firstOrFail();
    expect($log->subject_label)->toBe('F/001')->and($log->subject_id)->toBe($reg->id)->and($log->isSensitive())->toBeTrue();
});

// ---- Reading the trail

it('lets only the super admin and the committee open the audit log', function () {
    foreach (['super_admin', 'committee'] as $role) {
        $this->actingAs(person($role))->get(route('audit.index'))->assertOk()->assertSee('Audit log');
    }

    foreach (['exam_officer', 'invigilator', 'question_author', 'reviewer'] as $role) {
        $this->actingAs(person($role))->get(route('audit.index'))->assertForbidden();
        $this->actingAs(person($role))->get(route('audit.export'))->assertForbidden();
    }
});

it('sends signed-out visitors to the staff sign-in', function () {
    $this->get(route('audit.index'))->assertRedirect(route('login'));
});

it('filters the log by area, person, search and sensitivity', function () {
    $ada = person('exam_officer', 'Ada Officer');
    $bola = person('super_admin', 'Bola Admin');
    Audit::record('a', 'questions', 'Approved a question · #4 capital', ['actor' => $ada]);
    Audit::record('staff.deactivated', 'accounts', 'Deactivated a staff account · Chidi', ['actor' => $bola]);
    Audit::record('c', 'sittings', 'Froze a paper', ['actor' => $ada]);

    $viewer = person('super_admin');

    $this->actingAs($viewer)->get(route('audit.index', ['category' => 'questions']))->assertSee('Approved a question')->assertDontSee('Froze a paper');
    $this->actingAs($viewer)->get(route('audit.index', ['actor' => $bola->id]))->assertSee('Deactivated a staff account')->assertDontSee('Froze a paper');
    $this->actingAs($viewer)->get(route('audit.index', ['q' => 'capital']))->assertSee('Approved a question')->assertDontSee('Deactivated');
    $this->actingAs($viewer)->get(route('audit.index', ['sensitive' => 1]))->assertSee('Deactivated a staff account')->assertDontSee('Froze a paper')->assertSee('Sensitive');
});

it('shows old and new values under details', function () {
    Audit::record('officer.exams.update', 'exams', 'Edited an exam · Promotion', ['meta' => ['changes' => [
        ['model' => 'Exam', 'id' => 1, 'label' => 'Promotion', 'changed' => ['pass_mark' => ['from' => '50', 'to' => '60']]],
    ]]]);

    $this->actingAs(person('committee'))->get(route('audit.index'))->assertOk()->assertSee('pass mark')->assertSee('50')->assertSee('60');
});

it('exports the filtered log as CSV and records the export itself', function () {
    Audit::record('a', 'questions', 'Approved a question', ['actor' => person('reviewer', 'Rita Reviewer')]);
    Audit::record('c', 'sittings', 'Froze a paper');

    $viewer = person('super_admin');
    $response = $this->actingAs($viewer)->get(route('audit.export', ['category' => 'questions']));

    $response->assertOk()->assertDownload();
    $csv = $response->streamedContent();
    expect($csv)->toContain('Approved a question')->toContain('Rita Reviewer')->not->toContain('Froze a paper');

    expect(AuditLog::where('action', 'audit.export')->where('actor_id', $viewer->id)->count())->toBe(1);
});

it('does not let anyone change or remove entries through the app', function () {
    Audit::record('a', 'questions', 'Approved a question');
    $log = AuditLog::firstOrFail();

    foreach (['put', 'patch', 'delete'] as $verb) {
        $this->actingAs(person('super_admin'))->{$verb}('/audit/'.$log->id)->assertStatus(404);
    }
});

// ---- Candidate history

it('shows a candidate their registration story and exam events in time order', function () {
    $invigilator = person('invigilator', 'Ife Invigilator');
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'NO1', 'code' => 'NO1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50]);
    $sitting = ExamSession::create(['exam_id' => $exam->id, 'name' => 'Session 1', 'capacity' => 10, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHours(2)]);
    $c = StaffCandidate::create(['file_number' => 'F/042', 'surname' => 'Okoro', 'first_name' => 'Ada', 'cadre' => 'NO', 'level' => 'I']);
    $reg = ExamRegistration::create(['exam_id' => $exam->id, 'exam_session_id' => $sitting->id, 'candidate_type' => 'staff', 'candidate_id' => $c->id, 'seat_number' => '7', 'status' => 'in_progress']);

    $this->actingAs($invigilator);
    RegistrationLog::record($exam->id, 'checked_in', 'F/042', null, $reg->id);
    RegistrationLog::record($exam->id, 'pin_reissued', 'F/042', null, $reg->id);

    $paper = ExamPaper::create(['exam_session_id' => $sitting->id, 'generated_at' => now()]);
    $attempt = Attempt::create(['exam_registration_id' => $reg->id, 'exam_paper_id' => $paper->id, 'status' => 'in_progress', 'started_at' => now()]);
    AttemptEvent::create(['attempt_id' => $attempt->id, 'event_type' => 'started', 'ip_address' => '10.0.0.5']);
    AttemptEvent::create(['attempt_id' => $attempt->id, 'event_type' => 'tab_hidden', 'ip_address' => '10.0.0.5']);

    $page = $this->actingAs($invigilator)->get(route('registrations.history', $reg))->assertOk();
    $page->assertSee('Okoro')->assertSee('F/042')
        ->assertSeeInOrder(['Checked a candidate in', 'Issued a new PIN', 'Started the exam', 'Left the exam tab'])
        ->assertSee('Ife Invigilator')->assertSee('10.0.0.5');
});

it('keeps candidate history away from roles that do not need it', function () {
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'NO1', 'code' => 'NO1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'P', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50]);
    $c = StaffCandidate::create(['file_number' => 'F/1', 'surname' => 'A', 'first_name' => 'B', 'cadre' => 'NO', 'level' => 'I']);
    $reg = ExamRegistration::create(['exam_id' => $exam->id, 'candidate_type' => 'staff', 'candidate_id' => $c->id, 'status' => 'assigned']);

    foreach (['question_author', 'reviewer'] as $role) {
        $this->actingAs(person($role))->get(route('registrations.history', $reg))->assertForbidden();
    }
    foreach (['exam_officer', 'invigilator', 'super_admin', 'committee'] as $role) {
        $this->actingAs(person($role))->get(route('registrations.history', $reg))->assertOk();
    }
});

it('records an exam edit with every changed value, including its sections', function () {
    $officer = person('exam_officer', 'Ngozi Officer');
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'Nursing Officer I', 'code' => 'NO1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion Exam', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50, 'pass_mark_type' => 'percentage', 'negative_mark_fraction' => 0, 'result_release' => 'hidden']);
    $section = App\Models\ExamSection::create(['exam_id' => $exam->id, 'position' => 1, 'title' => 'General Knowledge', 'question_count' => 5, 'seconds_per_question' => 60, 'marks_per_question' => 1]);

    $this->actingAs($officer)->put(route('officer.exams.update', $exam), [
        'title' => 'Promotion Exam', 'exam_category_id' => $category->id, 'cycle_year' => 2026, 'paper_mode' => 'fixed_set',
        'pass_mark' => 65, 'pass_mark_type' => 'percentage', 'negative_mark_fraction' => 0, 'result_release' => 'hidden',
        'shuffle_questions' => 1, 'shuffle_options' => 1, 'forward_only' => 1,
        'sections' => [0 => ['id' => $section->id, 'title' => 'General Knowledge', 'question_count' => 8, 'seconds_per_question' => 60, 'marks_per_question' => 1, 'instructions' => '']],
    ])->assertSessionHasNoErrors();

    $log = AuditLog::where('action', 'officer.exams.update')->firstOrFail();
    expect($log->exam_id)->toBe($exam->id)->and($log->subject_label)->toBe('Promotion Exam');

    $changes = collect($log->meta['changes']);
    expect($changes->firstWhere('model', 'Exam')['changed']['pass_mark']['to'])->toEqual(65)
        ->and($changes->firstWhere('model', 'ExamSection')['changed']['question_count'])->toEqual(['from' => 5, 'to' => 8])
        // the section positions are shifted and put back while saving; that is not a change worth showing
        ->and($changes->firstWhere('model', 'ExamSection')['changed'])->not->toHaveKey('position');
});

it('carries the older logs into the audit trail when the table is first created', function () {
    $admin = person('super_admin', 'Chief Admin');
    $category = ExamCategory::create(['kind' => 'staff_cadre', 'name' => 'NO1', 'code' => 'NO1']);
    $exam = Exam::create(['exam_category_id' => $category->id, 'title' => 'Promotion', 'cycle_year' => 2026, 'paper_mode' => 'fixed_set', 'pass_mark' => 50]);

    // As it was before audit_logs existed: only the two older tables hold history.
    Illuminate\Support\Facades\Schema::dropIfExists('audit_logs');
    Illuminate\Support\Facades\DB::table('registration_logs')->insert([
        'exam_id' => $exam->id, 'exam_registration_id' => null, 'user_id' => $admin->id, 'action' => 'list_approved',
        'detail' => '120 candidates', 'reason' => null, 'created_at' => now()->subDays(3),
    ]);
    Illuminate\Support\Facades\DB::table('staff_logs')->insert([
        'actor_id' => $admin->id, 'target_id' => $admin->id, 'action' => 'password_reset', 'detail' => null, 'created_at' => now()->subDays(2),
    ]);

    (require database_path('migrations/2026_10_30_000001_create_audit_logs_table.php'))->up();

    $rows = AuditLog::orderBy('id')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->action)->toBe('reg.list_approved')->and($rows[0]->summary)->toBe('Approved an eligible list · 120 candidates')
        ->and($rows[0]->actor_name)->toBe('Chief Admin')->and($rows[0]->exam_id)->toBe($exam->id)
        ->and($rows[1]->action)->toBe('staff.password_reset')->and($rows[1]->category)->toBe('accounts');
});
