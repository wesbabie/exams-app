<?php

use App\Http\Controllers\Results\ResultController;
use App\Http\Middleware\EnsureStaffAccountReady;
use App\Http\Middleware\EnsureStaffRole;
use Illuminate\Support\Facades\Route;

// Results: exam officers and the super admin manage them; the committee can read, export and print.
Route::prefix('results')
    ->name('results.')
    ->middleware(['auth', EnsureStaffAccountReady::class, EnsureStaffRole::class.':super_admin,exam_officer,committee'])
    ->group(function () {
        Route::get('/', [ResultController::class, 'index'])->name('index');
        Route::get('exams/{exam}', [ResultController::class, 'show'])->name('exams.show');
        Route::get('exams/{exam}/export', [ResultController::class, 'export'])->name('exams.export');
        Route::get('exams/{exam}/print', [ResultController::class, 'print'])->name('exams.print');

        Route::middleware(EnsureStaffRole::class.':super_admin,exam_officer')->group(function () {
            Route::get('attempts/{attempt}', [ResultController::class, 'attempt'])->name('attempts.show');
            Route::get('exams/{exam}/analysis', [ResultController::class, 'analysis'])->name('exams.analysis');
            Route::post('exams/{exam}/release', [ResultController::class, 'release'])->name('exams.release');
            Route::post('exams/{exam}/withdraw', [ResultController::class, 'withdraw'])->name('exams.withdraw');
            Route::post('exams/{exam}/recalculate', [ResultController::class, 'recalculate'])->name('exams.recalculate');
            Route::get('exams/{exam}/slips', [ResultController::class, 'slips'])->name('exams.slips');
            Route::get('slips/{result}', [ResultController::class, 'slip'])->name('slip');
        });
    });
