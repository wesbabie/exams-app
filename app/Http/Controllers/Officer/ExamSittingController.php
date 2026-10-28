<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamSittingController extends Controller
{
    public function index(Exam $exam): View
    {
        $exam->load('category');

        $sittings = $exam->sessions()
            ->with('paper')
            ->withCount(['registrations as assigned_count' => fn ($q) => $q->where('status', '!=', 'removed')])
            ->orderBy('starts_at')
            ->get();

        return view('officer.exams.sittings', compact('exam', 'sittings'));
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $exam->sessions()->create($this->validated($request, $exam) + ['status' => 'scheduled']);

        return redirect()->route('officer.exams.sittings.index', $exam)->with('status', 'Sitting added.');
    }

    public function edit(Exam $exam, ExamSession $sitting): View
    {
        $this->belongs($exam, $sitting);

        return view('officer.exams.sitting-edit', compact('exam', 'sitting'));
    }

    public function update(Request $request, Exam $exam, ExamSession $sitting): RedirectResponse
    {
        $this->belongs($exam, $sitting);

        $data = $this->validated($request, $exam);
        $assigned = $sitting->registrations()->where('status', '!=', 'removed')->count();

        if ($data['capacity'] < $assigned) {
            throw ValidationException::withMessages([
                'capacity' => "{$assigned} candidates are already assigned to this sitting. Move some out before lowering the capacity.",
            ]);
        }

        $sitting->update($data);

        return redirect()->route('officer.exams.sittings.index', $exam)->with('status', 'Sitting saved.');
    }

    public function close(Exam $exam, ExamSession $sitting): RedirectResponse
    {
        $this->belongs($exam, $sitting);
        $sitting->update(['status' => 'closed']);

        return redirect()->route('officer.exams.sittings.index', $exam)->with('status', 'Sitting closed. Candidates can no longer sign in to it.');
    }

    public function reopen(Exam $exam, ExamSession $sitting): RedirectResponse
    {
        $this->belongs($exam, $sitting);
        $sitting->update(['status' => 'scheduled']);

        return redirect()->route('officer.exams.sittings.index', $exam)->with('status', 'Sitting reopened.');
    }

    public function destroy(Exam $exam, ExamSession $sitting): RedirectResponse
    {
        $this->belongs($exam, $sitting);

        if ($sitting->registrations()->where('status', '!=', 'removed')->exists()) {
            return redirect()->route('officer.exams.sittings.index', $exam)
                ->with('error', 'Candidates are assigned to this sitting. Move them out first.');
        }

        $sitting->delete();

        return redirect()->route('officer.exams.sittings.index', $exam)->with('status', 'Sitting deleted.');
    }

    private function belongs(Exam $exam, ExamSession $sitting): void
    {
        abort_unless($sitting->exam_id === $exam->id, 404);
    }

    private function validated(Request $request, Exam $exam): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['required', 'integer', 'between:1,2000'],
        ]);

        // The window must be long enough for the exam itself
        $needed = $exam->totalSeconds();
        $window = Carbon::parse($data['ends_at'])->getTimestamp() - Carbon::parse($data['starts_at'])->getTimestamp();

        if ($needed > 0 && $window < $needed) {
            $minutes = (int) ceil($needed / 60);
            throw ValidationException::withMessages([
                'ends_at' => "The exam takes about {$minutes} minutes, so the sitting needs to be at least that long. Candidates with extra time need more.",
            ]);
        }

        return $data;
    }
}
