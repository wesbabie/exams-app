<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Services\PaperGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamPaperController extends Controller
{
    public function preview(Exam $exam, ExamSession $sitting): View|RedirectResponse
    {
        $this->belongs($exam, $sitting);

        $paper = $sitting->paper;
        if (! $paper) {
            return $this->back($exam, 'error', 'No paper has been generated for this sitting yet.');
        }

        $exam->load('sections');
        $paper->load('questions.question.options', 'questions.question.topic');

        return view('officer.exams.paper', [
            'exam' => $exam,
            'sitting' => $sitting,
            'paper' => $paper,
            'bySection' => $paper->questions->groupBy('exam_section_id'),
        ]);
    }

    public function generate(Request $request, Exam $exam, ExamSession $sitting, PaperGenerator $generator): RedirectResponse
    {
        $this->belongs($exam, $sitting);

        try {
            $generator->generate($sitting, $request->user()->id);
        } catch (\DomainException $e) {
            return $this->back($exam, 'error', $e->getMessage());
        }

        return $this->back($exam, 'status', "Paper generated for {$sitting->name}. Preview it, then freeze it.");
    }

    public function generateAll(Request $request, Exam $exam, PaperGenerator $generator): RedirectResponse
    {
        $done = 0;

        foreach ($exam->sessions()->orderBy('starts_at')->get() as $sitting) {
            if ($sitting->paper?->isFrozen()) {
                continue;
            }

            try {
                $generator->generate($sitting, $request->user()->id);
                $done++;
            } catch (\DomainException $e) {
                return $this->back($exam, 'error', $done.' paper(s) generated, then: '.$e->getMessage());
            }
        }

        return $this->back($exam, $done ? 'status' : 'error', $done ? "{$done} paper(s) generated." : 'Every sitting already has a frozen paper.');
    }

    public function freeze(Exam $exam, ExamSession $sitting, PaperGenerator $generator): RedirectResponse
    {
        $this->belongs($exam, $sitting);

        $paper = $sitting->paper;
        if (! $paper || $paper->isFrozen()) {
            return $this->back($exam, 'error', 'There is no unfrozen paper to freeze.');
        }

        $generator->freeze($paper);

        return $this->back($exam, 'status', "The paper for {$sitting->name} is frozen. It can no longer change.");
    }

    public function freezeAll(Exam $exam, PaperGenerator $generator): RedirectResponse
    {
        $count = 0;

        foreach ($exam->sessions()->with('paper')->get() as $sitting) {
            if ($sitting->paper && ! $sitting->paper->isFrozen()) {
                $generator->freeze($sitting->paper);
                $count++;
            }
        }

        return $this->back($exam, $count ? 'status' : 'error', $count ? "{$count} paper(s) frozen." : 'There are no unfrozen papers.');
    }

    private function belongs(Exam $exam, ExamSession $sitting): void
    {
        abort_unless($sitting->exam_id === $exam->id, 404);
    }

    private function back(Exam $exam, string $key, string $message): RedirectResponse
    {
        return redirect()->route('officer.exams.sittings.index', $exam)->with($key, $message);
    }
}
