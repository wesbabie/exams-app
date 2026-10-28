<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $guarded = [];

    /** Null category means a shared topic (for example General Knowledge or Civil Service Rules). */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'exam_category_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function displayScope(): string
    {
        return $this->category?->name ?? 'Shared';
    }
}
