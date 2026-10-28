<?php

use App\Http\Controllers\Candidate\CandidateDashboardController;
use App\Http\Controllers\Candidate\CandidateLoginController;
use App\Http\Middleware\CandidateAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('exam')->name('candidate.')->group(function () {
    Route::get('login', [CandidateLoginController::class, 'create'])->name('login');
    Route::post('login', [CandidateLoginController::class, 'store'])->name('login.store');

    Route::middleware(CandidateAuth::class)->group(function () {
        Route::get('dashboard', [CandidateDashboardController::class, 'show'])->name('dashboard');
        Route::post('begin', [CandidateDashboardController::class, 'begin'])->name('begin');
        Route::post('logout', [CandidateLoginController::class, 'destroy'])->name('logout');
    });
});
