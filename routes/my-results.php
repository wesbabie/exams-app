<?php

use App\Http\Controllers\Candidate\MyResultsController;
use Illuminate\Support\Facades\Route;

// Staff candidates read their released results with a one-time code sent by email.
Route::prefix('my-results')->name('myresults.')->group(function () {
    Route::get('/', [MyResultsController::class, 'create'])->name('request');
    Route::post('/', [MyResultsController::class, 'send'])->name('send');
    Route::get('code', [MyResultsController::class, 'verifyForm'])->name('verify');
    Route::post('code', [MyResultsController::class, 'verify'])->name('verify.store');
    Route::get('view', [MyResultsController::class, 'show'])->name('show');
    Route::post('logout', [MyResultsController::class, 'logout'])->name('logout');
});
