<?php

use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Audit\CandidateHistoryController;
use App\Http\Middleware\EnsureStaffAccountReady;
use App\Http\Middleware\EnsureStaffRole;
use Illuminate\Support\Facades\Route;

// The audit trail is read-only. Super admin and the committee see all of it.
Route::prefix('audit')
    ->name('audit.')
    ->middleware(['auth', EnsureStaffAccountReady::class, EnsureStaffRole::class.':super_admin,committee'])
    ->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('export', [AuditLogController::class, 'export'])->name('export');
    });

// One candidate's story: who did what to their registration, and what happened during their exam.
Route::get('registrations/{registration}/history', [CandidateHistoryController::class, 'show'])
    ->middleware(['auth', EnsureStaffAccountReady::class, EnsureStaffRole::class.':super_admin,exam_officer,invigilator,committee'])
    ->name('registrations.history');
