<?php

namespace App\Http\Requests\Bank;

use App\Models\Question;
use App\Support\QuestionNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionRequest extends FormRequest
{
    private array $normalized = [];

    public function authorize(): bool
    {
        return true; // the controller checks the policy
    }

    public function rules(): array
    {
        return [
            'topic_id' => ['required', Rule::exists('topics', 'id')],
            'type' => ['required', Rule::in(array_keys(Question::TYPES))],
            'stem' => ['required', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'time_seconds' => ['nullable', 'integer', 'between:5,1800'],
            'review_due_on' => ['nullable', 'date', 'after:today'],
            'options' => ['nullable', 'array', 'max:12'],
            'options.*.body' => ['nullable', 'string', 'max:1000'],
            'correct_tf' => ['nullable', Rule::in(['true', 'false'])],
        ];
    }

    public function messages(): array
    {
        return [
            'topic_id.required' => 'Choose a topic.',
            'stem.required' => 'Write the question.',
            'review_due_on.after' => 'The review date must be in the future.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('type')) {
                return;
            }

            $type = (string) $this->input('type');
            $bodies = [];
            $correct = [];
            $position = 0;

            foreach ((array) $this->input('options', []) as $key => $row) {
                $bodies[$position] = $row['body'] ?? '';

                if ($type === 'multi_select' && ! empty($row['is_correct'])) {
                    $correct[] = $position;
                }
                if ($type === 'single_choice' && (string) $this->input('correct_single') === (string) $key) {
                    $correct[] = $position;
                }
                $position++;
            }

            if ($type === 'true_false') {
                $tf = $this->input('correct_tf');
                $correct = $tf === 'true' ? [0] : ($tf === 'false' ? [1] : []);
            }

            $result = QuestionNormalizer::normalize($type, $bodies, $correct);

            foreach ($result['errors'] as $message) {
                $validator->errors()->add('options', $message);
            }

            $this->normalized = $result['options'];
        });
    }

    /** The question's own columns. */
    public function questionAttributes(): array
    {
        return $this->safe()->only(['topic_id', 'type', 'stem', 'explanation', 'time_seconds', 'review_due_on']);
    }

    /** The checked options, in their final order. */
    public function normalizedOptions(): array
    {
        return $this->normalized;
    }
}
