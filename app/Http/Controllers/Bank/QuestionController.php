<?php

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\QuestionRequest;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Question::class);

        $user = $request->user();
        // Reviewers land on the review queue
        $status = $request->query('status', $user->role === 'reviewer' ? 'in_review' : 'all');

        $base = Question::visibleTo($user);
        $counts = (clone $base)->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');

        $questions = (clone $base)
            ->with(['topic', 'category', 'creator'])
            ->when(array_key_exists($status, Question::STATUSES), fn ($q) => $q->where('status', $status))
            ->when($request->filled('topic'), fn ($q) => $q->where('topic_id', $request->query('topic')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('q'), fn ($q) => $q->where('stem', 'like', '%'.$request->query('q').'%'))
            ->when($request->boolean('overdue'), fn ($q) => $q->where('status', 'approved')->whereDate('review_due_on', '<', today()))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('bank.questions.index', [
            'questions' => $questions,
            'status' => $status,
            'counts' => $counts,
            'topics' => Topic::with('category')->orderBy('name')->get(),
            'myDrafts' => Question::where('created_by', $user->id)->where('status', 'draft')->count(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Question::class);

        return view('bank.questions.form', [
            'question' => new Question(['type' => 'single_choice']),
            'mode' => 'create',
            'topics' => $this->topics(),
        ]);
    }

    public function store(QuestionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Question::class);

        $question = DB::transaction(function () use ($request) {
            $data = $request->questionAttributes();
            $topic = Topic::findOrFail($data['topic_id']);

            $question = Question::create($data + [
                'exam_category_id' => $topic->exam_category_id,
                'status' => 'draft',
                'version' => 1,
                'created_by' => $request->user()->id,
            ]);
            $question->options()->createMany($request->normalizedOptions());

            return $question;
        });

        return redirect()->route('bank.questions.show', $question)->with('status', 'Question saved as a draft.');
    }

    public function show(Question $question): View
    {
        Gate::authorize('view', $question);

        $question->load(['topic', 'category', 'options', 'creator', 'reviewer', 'previousVersion', 'nextVersion']);

        return view('bank.questions.show', compact('question'));
    }

    public function edit(Question $question): View|RedirectResponse
    {
        Gate::authorize('update', $question);

        return view('bank.questions.form', [
            'question' => $question->load('options'),
            'mode' => 'edit',
            'topics' => $this->topics(),
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        Gate::authorize('update', $question);

        DB::transaction(function () use ($request, $question) {
            $data = $request->questionAttributes();
            $topic = Topic::findOrFail($data['topic_id']);

            $question->update($data + ['exam_category_id' => $topic->exam_category_id]);
            $question->options()->delete();
            $question->options()->createMany($request->normalizedOptions());
        });

        return redirect()->route('bank.questions.show', $question)->with('status', 'Question saved.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        Gate::authorize('delete', $question);

        $question->delete();

        return redirect()->route('bank.questions.index')->with('status', 'Draft deleted.');
    }

    // ---- Review workflow: draft -> in review -> approved -> retired ----

    public function submit(Question $question): RedirectResponse
    {
        Gate::authorize('submit', $question);

        $question->update(['status' => 'in_review', 'submitted_at' => now(), 'review_note' => null]);

        return $this->back($question, 'Sent for review.');
    }

    public function withdraw(Question $question): RedirectResponse
    {
        Gate::authorize('withdraw', $question);

        $question->update(['status' => 'draft']);

        return $this->back($question, 'Taken back to draft.');
    }

    public function approve(Request $request, Question $question): RedirectResponse
    {
        Gate::authorize('review', $question);

        $this->markApproved($question, $request->user());

        return $this->back($question, 'Question approved. It can now be used in exams.');
    }

    public function returnForChanges(Request $request, Question $question): RedirectResponse
    {
        Gate::authorize('review', $question);

        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        $question->update([
            'status' => 'draft',
            'review_note' => $data['note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return $this->back($question, 'Returned to the author with your note.');
    }

    public function retire(Question $question): RedirectResponse
    {
        Gate::authorize('retire', $question);

        $question->update(['status' => 'retired']);

        return $this->back($question, 'Question retired. It will not be used in new papers.');
    }

    /** Copy an approved question into a new draft version. */
    public function newVersion(Request $request, Question $question): RedirectResponse
    {
        Gate::authorize('newVersion', $question);

        $copy = DB::transaction(function () use ($request, $question) {
            $copy = $question->replicate(['status', 'reviewed_by', 'reviewed_at', 'submitted_at', 'review_note']);
            $copy->fill([
                'status' => 'draft',
                'version' => $question->version + 1,
                'previous_version_id' => $question->id,
                'created_by' => $request->user()->id,
            ])->save();

            foreach ($question->options as $option) {
                $copy->options()->create($option->only(['body', 'is_correct', 'pin_last', 'position']));
            }

            return $copy;
        });

        return redirect()->route('bank.questions.edit', $copy)
            ->with('status', 'New version created as a draft. The approved version stays in use until this one is approved.');
    }

    // ---- Bulk actions ----

    /** Send every draft the user wrote for review (useful after a CSV import). */
    public function submitAll(Request $request): RedirectResponse
    {
        Gate::authorize('create', Question::class);

        $count = Question::where('created_by', $request->user()->id)
            ->where('status', 'draft')
            ->update(['status' => 'in_review', 'submitted_at' => now(), 'review_note' => null]);

        return redirect()->route('bank.questions.index')->with('status', "{$count} draft(s) sent for review.");
    }

    public function approveSelected(Request $request): RedirectResponse
    {
        $ids = array_filter((array) $request->input('ids', []));
        $approved = 0;
        $skipped = 0;

        foreach (Question::whereIn('id', $ids)->get() as $question) {
            if (Gate::allows('review', $question)) {
                $this->markApproved($question, $request->user());
                $approved++;
            } else {
                $skipped++;
            }
        }

        $message = "{$approved} question(s) approved.".($skipped ? " {$skipped} skipped (you cannot approve your own questions)." : '');

        return redirect()->route('bank.questions.index', ['status' => 'in_review'])->with('status', $message);
    }

    // ---- Helpers ----

    private function markApproved(Question $question, User $by): void
    {
        DB::transaction(function () use ($question, $by) {
            $question->update([
                'status' => 'approved',
                'reviewed_by' => $by->id,
                'reviewed_at' => now(),
                'review_note' => null,
            ]);

            // A newly approved version replaces the one it was copied from
            if ($question->previous_version_id) {
                Question::where('id', $question->previous_version_id)->where('status', 'approved')->update(['status' => 'retired']);
            }
        });
    }

    private function topics()
    {
        return Topic::with('category')->orderBy('name')->get();
    }

    private function back(Question $question, string $message): RedirectResponse
    {
        return redirect()->route('bank.questions.show', $question)->with('status', $message);
    }
}
