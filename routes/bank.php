<?php

use App\Http\Controllers\Bank\QuestionController;
use App\Http\Controllers\Bank\QuestionImportController;
use App\Http\Controllers\Bank\TopicController;
use App\Http\Middleware\EnsureStaffAccountReady;
use App\Http\Middleware\EnsureStaffRole;
use Illuminate\Support\Facades\Route;

Route::prefix('bank')
    ->name('bank.')
    ->middleware(['auth', EnsureStaffAccountReady::class, EnsureStaffRole::class.':super_admin,exam_officer,question_author,reviewer'])
    ->group(function () {
        // Questions
        Route::get('questions', [QuestionController::class, 'index'])->name('questions.index');
        Route::get('questions/create', [QuestionController::class, 'create'])->name('questions.create');
        Route::post('questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::post('questions/submit-all', [QuestionController::class, 'submitAll'])->name('questions.submit-all');
        Route::post('questions/approve-selected', [QuestionController::class, 'approveSelected'])->name('questions.approve-selected');
        Route::get('questions/{question}', [QuestionController::class, 'show'])->name('questions.show');
        Route::get('questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
        Route::put('questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
        Route::post('questions/{question}/submit', [QuestionController::class, 'submit'])->name('questions.submit');
        Route::post('questions/{question}/withdraw', [QuestionController::class, 'withdraw'])->name('questions.withdraw');
        Route::post('questions/{question}/approve', [QuestionController::class, 'approve'])->name('questions.approve');
        Route::post('questions/{question}/return', [QuestionController::class, 'returnForChanges'])->name('questions.return');
        Route::post('questions/{question}/retire', [QuestionController::class, 'retire'])->name('questions.retire');
        Route::post('questions/{question}/version', [QuestionController::class, 'newVersion'])->name('questions.version');

        // CSV import
        Route::get('import', [QuestionImportController::class, 'create'])->name('import.create');
        Route::get('import/template', [QuestionImportController::class, 'template'])->name('import.template');
        Route::post('import', [QuestionImportController::class, 'store'])->name('import.store');

        // Topics: exam officers and super admin only
        Route::middleware(EnsureStaffRole::class.':super_admin,exam_officer')->group(function () {
            Route::get('topics', [TopicController::class, 'index'])->name('topics.index');
            Route::post('topics', [TopicController::class, 'store'])->name('topics.store');
            Route::get('topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
            Route::put('topics/{topic}', [TopicController::class, 'update'])->name('topics.update');
        });
    });
