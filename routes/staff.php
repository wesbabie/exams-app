<?php

use App\Http\Controllers\Staff\AccountController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Middleware\EnsureStaffAccountReady;
use App\Http\Controllers\Staff\StaffLoginController;
use Illuminate\Support\Facades\Route;

// Staff sign-in (email and password). The route must be named "login" so Laravel's
// "auth" middleware knows where to send signed-out staff.
Route::get('login', [StaffLoginController::class, 'create'])->name('login');
Route::post('login', [StaffLoginController::class, 'store'])->name('login.store');

Route::middleware(['auth', EnsureStaffAccountReady::class])->group(function () {
    Route::post('logout', [StaffLoginController::class, 'destroy'])->name('logout');
    Route::get('staff', [StaffDashboardController::class, 'show'])->name('staff.dashboard');

    // Own password (also where staff are sent if they must change it)
    Route::get('account/password', [AccountController::class, 'edit'])->name('account.password.edit');
    Route::put('account/password', [AccountController::class, 'update'])->name('account.password.update');
});

// Per-role areas
require __DIR__.'/officer.php';
require __DIR__.'/bank.php';
require __DIR__.'/invigilator.php';
require __DIR__.'/results.php';
require __DIR__.'/admin.php';
