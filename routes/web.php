<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('front-door', ['intake' => \App\Models\Intake::currentlyOpen()]);
})->name('home');

require __DIR__.'/candidate.php';
require __DIR__.'/staff.php';
require __DIR__.'/intern.php';
