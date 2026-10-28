<?php

use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffLoginController;
use Illuminate\Support\Facades\Route;

// Staff sign-in (email and password). The route must be named "login" so Laravel's
// "auth" middleware knows where to send signed-out staff.
Route::get('login', [StaffLoginController::class, 'create'])->name('login');
Route::post('login', [StaffLoginController::class, 'store'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::post('logout', [StaffLoginController::class, 'destroy'])->name('logout');
    Route::get('staff', [StaffDashboardController::class, 'show'])->name('staff.dashboard');
});

// Per-role areas
require __DIR__.'/officer.php';
