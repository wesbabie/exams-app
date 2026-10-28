<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntakeController extends Controller
{
    public function index(): View
    {
        $intakes = Intake::withCount([
            'applicants',
            'applicants as submitted_count' => fn ($q) => $q->where('status', 'submitted'),
        ])->orderByDesc('opens_at')->get();

        return view('officer.intakes.index', compact('intakes'));
    }

    public function store(Request $request): RedirectResponse
    {
        Intake::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('officer.intakes.index')->with('status', 'Registration window added.');
    }

    public function edit(Intake $intake): View
    {
        return view('officer.intakes.edit', compact('intake'));
    }

    public function update(Request $request, Intake $intake): RedirectResponse
    {
        $intake->update($this->validated($request));

        return redirect()->route('officer.intakes.index')->with('status', 'Registration window updated.');
    }

    /** @return array{name:string, year:int, opens_at:string, closes_at:string} */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'between:2024,2100'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after:opens_at'],
        ], ['closes_at.after' => 'The closing date must be after the opening date.']);
    }
}
