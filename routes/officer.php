<?php

use App\Http\Controllers\Officer\ApplicantReviewController;
use App\Http\Controllers\Officer\ExamCategoryController;
use App\Http\Controllers\Officer\ExamCandidateController;
use App\Http\Controllers\Officer\ExamController;
use App\Http\Controllers\Officer\ExamPaperController;
use App\Http\Controllers\Officer\ExamQuestionController;
use App\Http\Controllers\Officer\ExamSittingController;
use App\Http\Controllers\Officer\IntakeController;
use App\Http\Middleware\EnsureStaffAccountReady;
use App\Http\Middleware\EnsureStaffRole;
use Illuminate\Support\Facades\Route;

Route::prefix('officer')
    ->name('officer.')
    ->middleware(['auth', EnsureStaffAccountReady::class, EnsureStaffRole::class.':exam_officer,super_admin'])
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

        // Questions for each section of an exam
        Route::get('exams/{exam}/sections/{section}/questions', [ExamQuestionController::class, 'edit'])->name('exams.sections.questions.edit');
        Route::post('exams/{exam}/sections/{section}/questions', [ExamQuestionController::class, 'add'])->name('exams.sections.questions.add');
        Route::delete('exams/{exam}/sections/{section}/questions/{question}', [ExamQuestionController::class, 'remove'])->name('exams.sections.questions.remove');
        Route::post('exams/{exam}/sections/{section}/questions/{question}/move', [ExamQuestionController::class, 'move'])->name('exams.sections.questions.move');
        Route::put('exams/{exam}/sections/{section}/blueprint', [ExamQuestionController::class, 'updateBlueprint'])->name('exams.sections.blueprint.update');

        // Candidates: eligible list, late additions, seats
        Route::get('exams/{exam}/candidates', [ExamCandidateController::class, 'index'])->name('exams.candidates.index');
        Route::get('exams/{exam}/candidates/template', [ExamCandidateController::class, 'template'])->name('exams.candidates.template');
        Route::post('exams/{exam}/candidates/import', [ExamCandidateController::class, 'import'])->name('exams.candidates.import');
        Route::post('exams/{exam}/candidates/auto-assign', [ExamCandidateController::class, 'autoAssign'])->name('exams.candidates.auto-assign');
        Route::post('exams/{exam}/candidates', [ExamCandidateController::class, 'add'])->name('exams.candidates.add');
        Route::delete('exams/{exam}/candidates/{registration}', [ExamCandidateController::class, 'remove'])->name('exams.candidates.remove');
        Route::post('exams/{exam}/candidates/{registration}/assign', [ExamCandidateController::class, 'assign'])->name('exams.candidates.assign');
        Route::post('exams/{exam}/candidates/{registration}/unassign', [ExamCandidateController::class, 'unassign'])->name('exams.candidates.unassign');
        Route::post('exams/{exam}/candidates/{registration}/extra-time', [ExamCandidateController::class, 'extraTime'])->name('exams.candidates.extra-time');
        Route::post('exams/{exam}/lists/{list}/approve', [ExamCandidateController::class, 'approveList'])->name('exams.lists.approve');
        Route::delete('exams/{exam}/lists/{list}', [ExamCandidateController::class, 'discardList'])->name('exams.lists.discard');

        // Papers: generated and frozen per sitting
        Route::post('exams/{exam}/papers/generate-all', [ExamPaperController::class, 'generateAll'])->name('exams.papers.generate-all');
        Route::post('exams/{exam}/papers/freeze-all', [ExamPaperController::class, 'freezeAll'])->name('exams.papers.freeze-all');
        Route::get('exams/{exam}/sittings/{sitting}/paper', [ExamPaperController::class, 'preview'])->name('exams.papers.preview');
        Route::post('exams/{exam}/sittings/{sitting}/paper', [ExamPaperController::class, 'generate'])->name('exams.papers.generate');
        Route::post('exams/{exam}/sittings/{sitting}/paper/freeze', [ExamPaperController::class, 'freeze'])->name('exams.papers.freeze');

        // Sittings
        Route::get('exams/{exam}/sittings', [ExamSittingController::class, 'index'])->name('exams.sittings.index');
        Route::post('exams/{exam}/sittings', [ExamSittingController::class, 'store'])->name('exams.sittings.store');
        Route::get('exams/{exam}/sittings/{sitting}/edit', [ExamSittingController::class, 'edit'])->name('exams.sittings.edit');
        Route::put('exams/{exam}/sittings/{sitting}', [ExamSittingController::class, 'update'])->name('exams.sittings.update');
        Route::post('exams/{exam}/sittings/{sitting}/close', [ExamSittingController::class, 'close'])->name('exams.sittings.close');
        Route::post('exams/{exam}/sittings/{sitting}/reopen', [ExamSittingController::class, 'reopen'])->name('exams.sittings.reopen');
        Route::delete('exams/{exam}/sittings/{sitting}', [ExamSittingController::class, 'destroy'])->name('exams.sittings.destroy');

        // Intern registration windows and applicant review
        Route::get('intakes', [IntakeController::class, 'index'])->name('intakes.index');
        Route::post('intakes', [IntakeController::class, 'store'])->name('intakes.store');
        Route::get('intakes/{intake}/edit', [IntakeController::class, 'edit'])->name('intakes.edit');
        Route::put('intakes/{intake}', [IntakeController::class, 'update'])->name('intakes.update');

        Route::get('applicants', [ApplicantReviewController::class, 'index'])->name('applicants.index');
        Route::get('applicants/{applicant}', [ApplicantReviewController::class, 'show'])->name('applicants.show');
        Route::get('applicants/{applicant}/documents/{kind}', [ApplicantReviewController::class, 'document'])->name('applicants.documents');
        Route::post('applicants/{applicant}/approve', [ApplicantReviewController::class, 'approve'])->name('applicants.approve');
        Route::post('applicants/{applicant}/reject', [ApplicantReviewController::class, 'reject'])->name('applicants.reject');
        Route::post('applicants/{applicant}/return', [ApplicantReviewController::class, 'returnForCorrection'])->name('applicants.return');
    });
