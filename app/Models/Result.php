<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Result extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'shortlisted' => 'boolean',
            'released_at' => 'datetime',
            'total_marks' => 'decimal:2',
            'max_marks' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SectionResult::class);
    }
}
