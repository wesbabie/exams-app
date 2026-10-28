<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attempt extends Model
{
    protected $guarded = [];

    protected $hidden = ['session_token_hash'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ExamRegistration::class, 'exam_registration_id');
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AttemptQuestion::class)->orderBy('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AttemptEvent::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class);
    }
}
