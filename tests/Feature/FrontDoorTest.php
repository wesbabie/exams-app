<?php

use App\Models\Intake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('offers the three ways in from the front door', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('candidate.login'), false)
        ->assertSee(route('login'), false)
        ->assertSee('Check my internship application');
});

it('offers the application when a registration window is open', function () {
    Intake::create(['name' => 'Internship Intake 2027', 'year' => 2027, 'opens_at' => now()->subDay(), 'closes_at' => now()->addWeek()]);

    $this->get('/')
        ->assertOk()
        ->assertSee(route('intern.register'), false)
        ->assertSee('Apply for the internship')
        ->assertSee('Internship Intake 2027');
});

it('shows every staff role a dashboard with the sidebar', function () {
    foreach (['super_admin', 'exam_officer', 'question_author', 'reviewer', 'invigilator', 'committee'] as $role) {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'is_active' => true, 'must_change_password' => false])->save();

        $this->actingAs($user)->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Skip to content')
            ->assertSee('Sign out');
    }
});

it('carries the ExamSphere name and no hospital branding', function () {
    $pages = [
        '/', route('login'), route('candidate.login'), route('intern.login'),
    ];

    foreach ($pages as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->toContain('ExamSphere')
            ->and(strtolower($html))->not->toContain('uath')
            ->and(strtolower($html))->not->toContain('teaching hospital')
            ->and(strtolower($html))->not->toContain('university of');
    }
});

it('sends the verification email under the ExamSphere name', function () {
    $mail = new App\Mail\ApplicantOtpMail('Ada', '123456', 15);

    expect($mail->envelope()->subject)->toBe('Your ExamSphere verification code');
    $mail->assertSeeInHtml('ExamSphere');
    $mail->assertDontSeeInHtml('UATH');
    $mail->assertDontSeeInHtml('Teaching Hospital');
});
