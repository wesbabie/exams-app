<?php

use App\Http\Controllers\Officer\ExamCategoryController;
use App\Http\Controllers\Officer\ExamController;
use App\Http\Middleware\EnsureStaffRole;
use Illuminate\Support\Facades\Route;

Route::prefix('officer')
    ->name('officer.')
    ->middleware(['auth', EnsureStaffRole::class.':exam_officer,super_admin'])
    ->group(function () {
        Route::get('categories', [ExamCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [ExamCategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [ExamCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [ExamCategoryController::class, 'update'])->name('categories.update');

        Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
        Route::get('exams/create', [ExamController::class, 'create'])->name('exams.create');
        Route::post('exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
        Route::get('exams/{exam}/edit', [ExamController::class, 'edit'])->name('exams.edit');
        Route::put('exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
        Route::post('exams/{exam}/submit', [ExamController::class, 'submit'])->name('exams.submit');
        Route::post('exams/{exam}/approve', [ExamController::class, 'approve'])->name('exams.approve');
        Route::post('exams/{exam}/reopen', [ExamController::class, 'reopen'])->name('exams.reopen');
    });
