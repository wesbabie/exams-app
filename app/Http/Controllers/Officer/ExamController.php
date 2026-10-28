<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Officer\ExamRequest;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamSectionQuestion;
use App\Models\SectionTopicBlueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $exams = Exam::query()
            ->with('category')
            ->withCount('sections')
            ->withSum('sections as total_questions', 'question_count')
            ->when(in_array($status, Exam::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('officer.exams.index', compact('exams', 'status'));
    }

    public function create(): View
    {
        $exam = new Exam([
            'paper_mode' => 'fixed_set', 'pass_mark' => 50, 'pass_mark_type' => 'percentage',
            'negative_mark_fraction' => 0, 'shuffle_questions' => true, 'shuffle_options' => true,
            'forward_only' => true, 'result_release' => 'hidden',
        ]);

        return view('officer.exams.form', [
            'exam' => $exam, 'mode' => 'create', 'sections' => [], 'categories' => $this->categories(),
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $exam = DB::transaction(function () use ($request) {
            $exam = Exam::create($request->examAttributes() + [
                'created_by' => $request->user()->id,
                'status' => 'draft',
            ]);
            $this->saveSections($exam, $request->validated('sections'));

            return $exam;
        });

        return redirect()->route('officer.exams.show', $exam)->with('status', 'Exam created as a draft.');
    }

    public function show(Exam $exam): View
    {
        $exam->load(['category', 'sections', 'creator', 'approver']);
        $sectionStatus = $exam->sectionStatuses();
        $issues = $exam->readinessIssues($sectionStatus);

        return view('officer.exams.show', compact('exam', 'sectionStatus', 'issues'));
    }

    public function edit(Exam $exam): View|RedirectResponse
    {
        if (! $exam->isDraft()) {
            return $this->locked($exam);
        }

        $sections = $exam->sections->map(fn ($s) => [
            'id' => $s->id,
            'title' => $s->title,
            'instructions' => $s->instructions,
            'question_count' => $s->question_count,
            'seconds_per_question' => $s->seconds_per_question,
            'marks_per_question' => rtrim(rtrim((string) $s->marks_per_question, '0'), '.'),
        ])->all();

        return view('officer.exams.form', [
            'exam' => $exam, 'mode' => 'edit', 'sections' => $sections, 'categories' => $this->categories(),
        ]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        if (! $exam->isDraft()) {
            return $this->locked($exam);
        }

        $modeChanged = $exam->paper_mode !== $request->input('paper_mode');

        DB::transaction(function () use ($request, $exam, $modeChanged) {
            $exam->update($request->examAttributes());
            $this->saveSections($exam, $request->validated('sections'));

            // Chosen questions and topic counts belong to one paper mode, so switching mode clears them
            if ($modeChanged) {
                $ids = $exam->sections()->pluck('id');
                ExamSectionQuestion::whereIn('exam_section_id', $ids)->delete();
                SectionTopicBlueprint::whereIn('exam_section_id', $ids)->delete();
            }
        });

        return redirect()->route('officer.exams.show', $exam)->with('status', 'Exam saved.');
    }

    // ---- Status changes: draft -> in review -> approved ----

    public function submit(Exam $exam): RedirectResponse
    {
        if (! $exam->isDraft()) {
            return $this->back($exam, 'error', 'Only a draft can be sent for review.');
        }
        if ($exam->sections()->count() === 0) {
            return $this->back($exam, 'error', 'Add at least one section before sending the exam for review.');
        }
        if ($exam->readinessIssues()) {
            return $this->back($exam, 'error', 'The exam is not ready yet. See the checklist on this page.');
        }

        $exam->update(['status' => 'in_review']);

        return $this->back($exam, 'status', 'Sent for review. Another exam officer needs to approve it.');
    }

    public function approve(Request $request, Exam $exam): RedirectResponse
    {
        if ($exam->status !== 'in_review') {
            return $this->back($exam, 'error', 'Only an exam that is in review can be approved.');
        }
        // Two-person rule: the creator cannot approve their own exam
        if ($exam->created_by === $request->user()->id) {
            return $this->back($exam, 'error', 'You created this exam, so someone else needs to approve it.');
        }

        // Questions can be retired after the exam was sent for review, so check again
        if ($exam->readinessIssues()) {
            return $this->back($exam, 'error', 'The exam is not ready any more. See the checklist on this page.');
        }

        $exam->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        return $this->back($exam, 'status', 'Exam approved. Its sections, timers and marks are now locked.');
    }

    public function reopen(Exam $exam): RedirectResponse
    {
        if ($exam->status !== 'in_review') {
            return $this->back($exam, 'error', 'Only an exam in review can be returned to draft.');
        }

        $exam->update(['status' => 'draft']);

        return $this->back($exam, 'status', 'Returned to draft. You can edit it again.');
    }

    // ---- Helpers ----

    private function categories()
    {
        return ExamCategory::where('is_active', true)->orderBy('kind')->orderBy('name')->get()->groupBy('kind');
    }

    private function locked(Exam $exam): RedirectResponse
    {
        return $this->back($exam, 'error', 'Only a draft exam can be edited. Approved exams are locked.');
    }

    private function back(Exam $exam, string $key, string $message): RedirectResponse
    {
        return redirect()->route('officer.exams.show', $exam)->with($key, $message);
    }

    /**
     * Update existing sections by id (so questions already assigned to them survive),
     * create new ones, and remove the ones that were taken out of the form.
     */
    private function saveSections(Exam $exam, array $rows): void
    {
        // Move current positions out of the way so reordering never hits the unique (exam, position) index
        $exam->sections()->update(['position' => DB::raw('position + 100')]);

        $keep = [];

        foreach (array_values($rows) as $i => $row) {
            $attributes = [
                'title' => $row['title'],
                'position' => $i + 1,
                'instructions' => $row['instructions'] ?? null,
                'question_count' => $row['question_count'],
                'seconds_per_question' => $row['seconds_per_question'],
                'marks_per_question' => $row['marks_per_question'],
            ];

            $section = isset($row['id']) ? $exam->sections()->whereKey($row['id'])->first() : null;

            if ($section) {
                $section->update($attributes);
            } else {
                $section = $exam->sections()->create($attributes);
            }

            $keep[] = $section->id;
        }

        $exam->sections()->whereNotIn('id', $keep)->delete();
    }
}
