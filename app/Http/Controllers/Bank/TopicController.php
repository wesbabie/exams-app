<?php

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Controller;
use App\Models\ExamCategory;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function index(): View
    {
        $topics = Topic::with('category')
            ->withCount(['questions', 'questions as approved_count' => fn ($q) => $q->where('status', 'approved')])
            ->orderByRaw('exam_category_id is not null')
            ->orderBy('name')
            ->get();

        return view('bank.topics.index', [
            'topics' => $topics,
            'categories' => ExamCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Topic::create($this->validated($request));

        return redirect()->route('bank.topics.index')->with('status', 'Topic added.');
    }

    public function edit(Topic $topic): View
    {
        return view('bank.topics.edit', [
            'topic' => $topic,
            'categories' => ExamCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Topic $topic): RedirectResponse
    {
        $topic->update($this->validated($request, $topic));

        return redirect()->route('bank.topics.index')->with('status', 'Topic updated.');
    }

    private function validated(Request $request, ?Topic $topic = null): array
    {
        $categoryId = $request->filled('exam_category_id') ? (int) $request->input('exam_category_id') : null;

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('topics', 'name')
                    ->where(fn ($q) => $categoryId ? $q->where('exam_category_id', $categoryId) : $q->whereNull('exam_category_id'))
                    ->ignore($topic?->id),
            ],
            'exam_category_id' => ['nullable', Rule::exists('exam_categories', 'id')],
        ], ['name.unique' => 'There is already a topic with this name in that scope.']);

        $data['exam_category_id'] = $categoryId;

        return $data;
    }
}
