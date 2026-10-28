<?php

use App\Http\Controllers\Candidate\CandidateLoginController;
use Illuminate\Support\Facades\Route;

Route::prefix('exam')->name('candidate.')->group(function () {
    Route::middleware('guest:candidate')->group(function () {
        Route::get('login', [CandidateLoginController::class, 'create'])->name('login');
        Route::post('login', [CandidateLoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:candidate')->group(function () {
        Route::get('start', [CandidateLoginController::class, 'start'])->name('start');
        Route::post('logout', [CandidateLoginController::class, 'destroy'])->name('logout');
    });
});
