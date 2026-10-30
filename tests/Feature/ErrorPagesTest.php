<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('shows a branded 404 page', function () {
    $this->get('/no-such-page')->assertNotFound()->assertSee('We can’t find that page')->assertSee('ExamSphere')->assertSee('Go to the start page');
});

it('shows a calm expired-session page that tells candidates their answers are safe', function () {
    Route::get('/exam/_expired', fn () => abort(419));
    Route::get('/_expired', fn () => abort(419));

    $this->get('/exam/_expired')->assertStatus(419)->assertSee('Your answers are safe')->assertSee('Sign in again');
    $this->get('/_expired')->assertStatus(419)->assertSee('This page expired')->assertDontSee('Your answers are safe');
});

it('shows the reason on a 403 when one was given, and a plain message otherwise', function () {
    Route::get('/_forbidden-reason', fn () => abort(403, 'Your application can no longer be changed.'));
    Route::get('/_forbidden', fn () => abort(403));

    $this->get('/_forbidden-reason')->assertForbidden()->assertSee('Your application can no longer be changed.');
    $this->get('/_forbidden')->assertForbidden()->assertSee('does not have access');
});

it('shows friendly 429 and 500 pages', function () {
    Route::get('/_busy', fn () => abort(429));
    Route::get('/_broken', fn () => abort(500));

    $this->get('/_busy')->assertStatus(429)->assertSee('Too many tries');
    $this->get('/_broken')->assertStatus(500)->assertSee('Something went wrong on our side');
});
