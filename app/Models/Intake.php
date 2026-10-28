<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A registration window for intern applicants.
 */
class Intake extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('opens_at', '<=', now())->where('closes_at', '>=', now());
    }

    /** The window new applicants register in: the open one that opened most recently. */
    public static function currentlyOpen(): ?self
    {
        return static::open()->orderByDesc('opens_at')->first();
    }

    public function isOpen(): bool
    {
        return $this->opens_at->lte(now()) && $this->closes_at->gte(now());
    }

    public function stateLabel(): string
    {
        return match (true) {
            $this->opens_at->gt(now()) => 'Not open yet',
            $this->closes_at->lt(now()) => 'Closed',
            default => 'Open',
        };
    }
}
