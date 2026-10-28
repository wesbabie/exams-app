<?php

namespace App\Http\Requests\Officer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // access is enforced by the route middleware
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'shuffle_questions' => $this->boolean('shuffle_questions'),
            'shuffle_options' => $this->boolean('shuffle_options'),
            'forward_only' => $this->boolean('forward_only'),
        ]);
    }

    public function rules(): array
    {
        return [
            'exam_category_id' => ['required', Rule::exists('exam_categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'cycle_year' => ['nullable', 'integer', 'between:2000,2100'],
            'paper_mode' => ['required', Rule::in(['fixed_set', 'pool_draw'])],

            'pass_mark_type' => ['required', Rule::in(['percentage', 'absolute'])],
            'pass_mark' => array_values(array_filter([
                'required', 'numeric', 'min:0',
                $this->input('pass_mark_type') === 'percentage' ? 'max:100' : null,
            ])),
            'negative_mark_fraction' => ['required', 'numeric', 'between:0,1'],
            'top_n' => ['nullable', 'integer', 'min:1'],
            'tie_break_rule' => ['nullable', Rule::in(['field_section_score', 'earliest_submission'])],
            'result_release' => ['required', Rule::in(['hidden', 'pass_fail', 'full_breakdown'])],

            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
            'forward_only' => ['boolean'],

            'sections' => ['required', 'array', 'min:1', 'max:20'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.instructions' => ['nullable', 'string', 'max:2000'],
            'sections.*.question_count' => ['required', 'integer', 'between:1,500'],
            'sections.*.seconds_per_question' => ['required', 'integer', 'between:5,1800'],
            'sections.*.marks_per_question' => ['required', 'numeric', 'between:0.25,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'sections.required' => 'Add at least one section.',
            'sections.*.title.required' => 'Every section needs a title.',
            'sections.*.question_count.required' => 'Every section needs a question count.',
            'pass_mark.max' => 'A percentage pass mark cannot be more than 100.',
        ];
    }

    /** Exam columns only (everything except the sections list). */
    public function examAttributes(): array
    {
        return $this->safe()->except('sections');
    }
}
