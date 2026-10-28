<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSectionQuestion;
use App\Models\Question;
use App\Models\SectionTopicBlueprint;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamQuestionController extends Controller
{
    /** Fixed-set exams: choose questions. Pool-draw exams: set topic counts. */
    public function edit(Request $request, Exam $exam, ExamSection $section): View
    {
        $this->belongs($exam, $section);

        $exam->load(['category', 'sections']);
        $statuses = $exam->sectionStatuses();
        $topics = $this->allowedTopics($exam);

        $common = [
            'exam' => $exam,
            'section' => $section,
            'statuses' => $statuses,
            'editable' => $exam->isDraft(),
        ];

        if ($exam->paper_mode === 'pool_draw') {
            return view('officer.exams.blueprint', $common + [
                'topics' => $topics,
                'counts' => $section->blueprints()->pluck('question_count', 'topic_id'),
            ]);
        }

        $usedIds = $this->usedQuestionIds($exam);

        $available = Question::with('topic')
            ->where('status', 'approved')
            ->whereIn('topic_id', $topics->pluck('id'))
            ->whereNotIn('id', $usedIds)
            ->when($request->filled('topic'), fn ($q) => $q->where('topic_id', $request->query('topic')))
            ->when($request->filled('q'), fn ($q) => $q->where('stem', 'like', '%'.$request->query('q').'%'))
            ->orderBy('topic_id')->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('officer.exams.questions', $common + [
            'selected' => $section->fixedQuestions()->with('question.topic')->get(),
            'available' => $available,
            'topics' => $topics,
        ]);
    }

    public function add(Request $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $this->belongs($exam, $section);

        if ($blocked = $this->requireDraft($exam, $section)) {
            return $blocked;
        }

        $data = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']], [
            'ids.required' => 'Tick at least one question to add.',
        ]);
        $ids = array_values(array_unique($data['ids']));

        $room = $section->question_count - $section->fixedQuestions()->count();
        if ($room <= 0) {
            return $this->back($exam, $section, 'error', "This section already has all {$section->question_count} questions.");
        }

        $questions = Question::whereIn('id', $ids)
            ->where('status', 'approved')
            ->whereIn('topic_id', $this->allowedTopics($exam)->pluck('id'))
            ->whereNotIn('id', $this->usedQuestionIds($exam))
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $ids))
            ->values();

        if ($questions->isEmpty()) {
            return $this->back($exam, $section, 'error', 'None of those questions can be added. They may already be used in this exam.');
        }
        if ($questions->count() > $room) {
            return $this->back($exam, $section, 'error', "You can add {$room} more question(s) to this section, but you ticked {$questions->count()}.");
        }

        DB::transaction(function () use ($section, $questions) {
            $position = (int) $section->fixedQuestions()->max('position');
            foreach ($questions as $question) {
                ExamSectionQuestion::create([
                    'exam_section_id' => $section->id,
                    'question_id' => $question->id,
                    'position' => ++$position,
                ]);
            }
        });

        return $this->back($exam, $section, 'status', $questions->count().' question(s) added.');
    }

    public function remove(Exam $exam, ExamSection $section, int $question): RedirectResponse
    {
        $this->belongs($exam, $section);

        if ($blocked = $this->requireDraft($exam, $section)) {
            return $blocked;
        }

        DB::transaction(function () use ($section, $question) {
            $section->fixedQuestions()->where('question_id', $question)->delete();

            // Close the gap in the order
            $section->fixedQuestions()->get()->each(fn ($row, $i) => $row->update(['position' => $i + 1]));
        });

        return $this->back($exam, $section, 'status', 'Question removed from this section.');
    }

    public function move(Request $request, Exam $exam, ExamSection $section, int $question): RedirectResponse
    {
        $this->belongs($exam, $section);

        if ($blocked = $this->requireDraft($exam, $section)) {
            return $blocked;
        }

        $direction = $request->input('direction') === 'up' ? -1 : 1;
        $rows = $section->fixedQuestions()->get()->values();
        $index = $rows->search(fn ($r) => $r->question_id === $question);

        if ($index !== false && isset($rows[$index + $direction])) {
            $a = $rows[$index];
            $b = $rows[$index + $direction];
            [$pa, $pb] = [$a->position, $b->position];
            $a->update(['position' => $pb]);
            $b->update(['position' => $pa]);
        }

        return $this->back($exam, $section, null, null);
    }

    /** Pool-draw exams: save how many questions each topic contributes to this section. */
    public function updateBlueprint(Request $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $this->belongs($exam, $section);

        if ($blocked = $this->requireDraft($exam, $section)) {
            return $blocked;
        }

        $request->validate(['counts' => ['nullable', 'array'], 'counts.*' => ['nullable', 'integer', 'min:0', 'max:500']]);

        $topics = $this->allowedTopics($exam)->keyBy('id');
        $counts = collect($request->input('counts', []))
            ->map(fn ($c) => (int) $c)
            ->filter(fn ($c, $topicId) => $c > 0 && $topics->has($topicId));

        if ($counts->sum() > $section->question_count) {
            return $this->back($exam, $section, 'error', "The counts add up to {$counts->sum()}, but this section has only {$section->question_count} questions.");
        }

        foreach ($counts as $topicId => $count) {
            if ($count > $topics[$topicId]->approved_count) {
                return $this->back($exam, $section, 'error', "\"{$topics[$topicId]->name}\" has only {$topics[$topicId]->approved_count} approved question(s), but you asked for {$count}.");
            }
        }

        DB::transaction(function () use ($section, $counts) {
            $section->blueprints()->delete();
            foreach ($counts as $topicId => $count) {
                SectionTopicBlueprint::create([
                    'exam_section_id' => $section->id, 'topic_id' => $topicId, 'question_count' => $count,
                ]);
            }
        });

        return $this->back($exam, $section, 'status', 'Topic counts saved.');
    }

    // ---- Helpers ----

    private function belongs(Exam $exam, ExamSection $section): void
    {
        abort_unless($section->exam_id === $exam->id, 404);
    }

    private function requireDraft(Exam $exam, ExamSection $section): ?RedirectResponse
    {
        return $exam->isDraft()
            ? null
            : $this->back($exam, $section, 'error', 'Only a draft exam can be changed. Approved exams are locked.');
    }

    /** Topics this exam may use: shared topics plus the ones for its own category. */
    private function allowedTopics(Exam $exam)
    {
        return Topic::with('category')
            ->withCount(['questions as approved_count' => fn ($q) => $q->where('status', 'approved')])
            ->where(fn ($q) => $q->whereNull('exam_category_id')->orWhere('exam_category_id', $exam->exam_category_id))
            ->orderBy('name')
            ->get();
    }

    /** A question can only be used once in an exam. */
    private function usedQuestionIds(Exam $exam): array
    {
        return ExamSectionQuestion::whereIn('exam_section_id', $exam->sections()->pluck('id'))
            ->pluck('question_id')
            ->all();
    }

    private function back(Exam $exam, ExamSection $section, ?string $key, ?string $message): RedirectResponse
    {
        $redirect = redirect()->route('officer.exams.sections.questions.edit', [$exam, $section]);

        return $key ? $redirect->with($key, $message) : $redirect;
    }
}
