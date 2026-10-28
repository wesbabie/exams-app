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
