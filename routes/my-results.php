<?php

use App\Http\Controllers\Candidate\MyResultsController;
use Illuminate\Support\Facades\Route;

// Candidates read their own released results with the number and PIN from their exam-day slip,
// or, if they lost the PIN, with a one-time code emailed to the address on record.
Route::prefix('my-results')->name('myresults.')->group(function () {
    Route::get('/', [MyResultsController::class, 'create'])->name('request');
    Route::post('/', [MyResultsController::class, 'pin'])->name('pin');
    Route::get('email', [MyResultsController::class, 'emailForm'])->name('email');
    Route::post('email', [MyResultsController::class, 'send'])->name('send');
    Route::get('code', [MyResultsController::class, 'verifyForm'])->name('verify');
    Route::post('code', [MyResultsController::class, 'verify'])->name('verify.store');
    Route::get('view', [MyResultsController::class, 'show'])->name('show');
    Route::post('logout', [MyResultsController::class, 'logout'])->name('logout');
});
