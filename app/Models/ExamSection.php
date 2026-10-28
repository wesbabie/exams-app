<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSection extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'marks_per_question' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** Fixed-set exams: the chosen questions, in order. */
    public function fixedQuestions(): HasMany
    {
        return $this->hasMany(ExamSectionQuestion::class)->orderBy('position');
    }

    /** Pool-draw exams: how many questions to draw from each topic. */
    public function blueprints(): HasMany
    {
        return $this->hasMany(SectionTopicBlueprint::class);
    }
}
