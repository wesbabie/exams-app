<?php

use App\Http\Controllers\Intern\DashboardController;
use App\Http\Controllers\Intern\DocumentController;
use App\Http\Controllers\Intern\LoginController;
use App\Http\Controllers\Intern\ProfileController;
use App\Http\Controllers\Intern\RegisterController;
use App\Http\Controllers\Intern\SlipController;
use App\Http\Controllers\Intern\VerifyEmailController;
use App\Http\Middleware\EnsureApplicantVerified;
use Illuminate\Support\Facades\Route;

Route::prefix('intern')->name('intern.')->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::middleware('auth:applicant')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('verify', [VerifyEmailController::class, 'show'])->name('verify');
        Route::post('verify', [VerifyEmailController::class, 'store'])->middleware('throttle:10,1')->name('verify.store');
        Route::post('verify/resend', [VerifyEmailController::class, 'resend'])->name('verify.resend');

        Route::middleware(EnsureApplicantVerified::class)->group(function () {
            Route::get('/', [DashboardController::class, 'show'])->name('dashboard');
            Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::put('profile/submit', [ProfileController::class, 'submit'])->name('profile.submit');
            Route::get('documents/{kind}', [DocumentController::class, 'show'])->name('documents.show');
            Route::get('slip', [SlipController::class, 'show'])->name('slip');
        });
    });
});
