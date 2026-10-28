<?php

namespace App\Http\Controllers\Intern;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(Request $request): View
    {
        $applicant = $request->user('applicant')->load(['intake', 'category']);

        $registration = $applicant->status === 'approved'
            ? $applicant->registrations()->with(['exam', 'examSession'])->latest('id')->first()
            : null;

        return view('intern.dashboard', compact('applicant', 'registration'));
    }
}
