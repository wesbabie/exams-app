<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionResult extends Model
{
    protected $guarded = [];

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'exam_section_id');
    }
}
