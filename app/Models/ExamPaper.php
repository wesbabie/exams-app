<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** The paper for one sitting. Once frozen it never changes. */
class ExamPaper extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'frozen_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamPaperQuestion::class)->orderBy('exam_section_id')->orderBy('position');
    }

    public function isFrozen(): bool
    {
        return $this->frozen_at !== null;
    }
}
