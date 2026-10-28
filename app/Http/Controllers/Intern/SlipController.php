<?php

namespace App\Http\Controllers\Intern;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlipController extends Controller
{
    /** The exam slip. It does not carry a PIN: the invigilator hands that out at check-in. */
    public function show(Request $request): View
    {
        $applicant = $request->user('applicant')->load('category');

        abort_unless($applicant->status === 'approved', 404);

        $registration = $applicant->registrations()->with(['exam', 'examSession'])->latest('id')->first();

        abort_unless($registration?->examSession, 404, 'Your exam sitting has not been assigned yet.');

        return view('intern.slip', compact('applicant', 'registration'));
    }
}
