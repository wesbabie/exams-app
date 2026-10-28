<?php

use App\Http\Controllers\Admin\StaffAccountController;
use App\Http\Middleware\EnsureStaffAccountReady;
use App\Http\Middleware\EnsureStaffRole;
use Illuminate\Support\Facades\Route;

// Staff accounts: super admin only
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureStaffAccountReady::class, EnsureStaffRole::class.':super_admin'])
    ->group(function () {
        Route::get('staff', [StaffAccountController::class, 'index'])->name('staff.index');
        Route::get('staff/create', [StaffAccountController::class, 'create'])->name('staff.create');
        Route::post('staff', [StaffAccountController::class, 'store'])->name('staff.store');
        Route::get('staff/credentials', [StaffAccountController::class, 'showCredentials'])->name('staff.credentials');
        Route::get('staff/{staff}/edit', [StaffAccountController::class, 'edit'])->name('staff.edit');
        Route::put('staff/{staff}', [StaffAccountController::class, 'update'])->name('staff.update');
        Route::post('staff/{staff}/deactivate', [StaffAccountController::class, 'deactivate'])->name('staff.deactivate');
        Route::post('staff/{staff}/reactivate', [StaffAccountController::class, 'reactivate'])->name('staff.reactivate');
        Route::post('staff/{staff}/reset-password', [StaffAccountController::class, 'resetPassword'])->name('staff.reset-password');
    });
