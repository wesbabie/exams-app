<?php

use App\Http\Controllers\Candidate\CandidateDashboardController;
use App\Http\Controllers\Candidate\CandidateLoginController;
use App\Http\Controllers\Candidate\ExamTakeController;
use App\Http\Middleware\CandidateAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('exam')->name('candidate.')->group(function () {
    Route::get('login', [CandidateLoginController::class, 'create'])->name('login');
    Route::post('login', [CandidateLoginController::class, 'store'])->name('login.store');

    Route::middleware(CandidateAuth::class)->group(function () {
        Route::get('dashboard', [CandidateDashboardController::class, 'show'])->name('dashboard');
        Route::post('begin', [CandidateDashboardController::class, 'begin'])->name('begin');

        // The exam itself
        Route::get('take', [ExamTakeController::class, 'show'])->name('exam.take');
        Route::post('take/section', [ExamTakeController::class, 'beginSection'])->name('exam.section-begin');
        Route::post('take/answer', [ExamTakeController::class, 'answer'])->name('exam.answer');
        Route::post('take/next', [ExamTakeController::class, 'next'])->name('exam.next');
        Route::post('take/event', [ExamTakeController::class, 'event'])->name('exam.event');
        Route::get('finished', [ExamTakeController::class, 'finished'])->name('exam.finished');
        Route::post('logout', [CandidateLoginController::class, 'destroy'])->name('logout');
    });
});
