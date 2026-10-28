<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One question as served to one candidate: their order, their option order, their deadline and their answer. */
class AttemptQuestion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'option_order' => 'array',
            'selected_option_ids' => 'array',
            'served_at' => 'datetime',
            'deadline_at' => 'datetime',
            'answered_at' => 'datetime',
            'is_correct' => 'boolean',
            'marks_awarded' => 'decimal:2',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    public function paperQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamPaperQuestion::class, 'exam_paper_question_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'exam_section_id');
    }
}
