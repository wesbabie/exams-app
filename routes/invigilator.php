<?php

use App\Http\Controllers\Invigilator\CheckInController;
use App\Http\Middleware\EnsureStaffAccountReady;
use App\Http\Middleware\EnsureStaffRole;
use Illuminate\Support\Facades\Route;

Route::prefix('invigilator')
    ->name('invigilator.')
    ->middleware(['auth', EnsureStaffAccountReady::class, EnsureStaffRole::class.':invigilator,exam_officer,super_admin'])
    ->group(function () {
        Route::get('sittings', [CheckInController::class, 'index'])->name('sittings.index');
        Route::get('sittings/{sitting}', [CheckInController::class, 'show'])->name('sittings.show');
        Route::post('sittings/{sitting}/admit-all', [CheckInController::class, 'admitAll'])->name('sittings.admit-all');

        Route::get('registrations/{registration}/slip', [CheckInController::class, 'slip'])->name('slip');
        Route::post('registrations/{registration}/check-in', [CheckInController::class, 'checkIn'])->name('check-in');
        Route::post('registrations/{registration}/admit', [CheckInController::class, 'admit'])->name('admit');
        Route::post('registrations/{registration}/revoke', [CheckInController::class, 'revoke'])->name('revoke');
        Route::post('registrations/{registration}/reissue-pin', [CheckInController::class, 'reissuePin'])->name('reissue-pin');
        Route::post('registrations/{registration}/unlock', [CheckInController::class, 'unlock'])->name('unlock');
        Route::post('registrations/{registration}/undo-check-in', [CheckInController::class, 'undoCheckIn'])->name('undo-check-in');
        Route::post('registrations/{registration}/absent', [CheckInController::class, 'markAbsent'])->name('absent');
    });
