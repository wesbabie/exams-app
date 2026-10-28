<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\ExamCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ExamCategory::withCount('exams')->orderBy('kind')->orderBy('name')->get();

        return view('officer.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        ExamCategory::create($this->validated($request));

        return redirect()->route('officer.categories.index')->with('status', 'Category added.');
    }

    public function edit(ExamCategory $category): View
    {
        return view('officer.categories.edit', compact('category'));
    }

    public function update(Request $request, ExamCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('officer.categories.index')->with('status', 'Category updated.');
    }

    private function validated(Request $request, ?ExamCategory $category = null): array
    {
        // Codes are stored in upper case
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(ExamCategory::KINDS))],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('exam_categories', 'code')->ignore($category?->id)],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
