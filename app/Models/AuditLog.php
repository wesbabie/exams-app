<?php

namespace App\Models;

use App\Support\AuditCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line in the audit trail: who did what, to what, and when.
 * Rows are only ever added. Nothing in the app edits or deletes them.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'meta' => 'array'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function categoryLabel(): string
    {
        return AuditCatalog::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function isSensitive(): bool
    {
        return AuditCatalog::isSensitive($this->action);
    }

    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($f['actor'] ?? null, fn ($q, $v) => $q->where('actor_id', $v))
            ->when($f['exam'] ?? null, fn ($q, $v) => $q->where('exam_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($v)->startOfDay()))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($v)->endOfDay()))
            ->when($f['sensitive'] ?? null, fn ($q) => $q->whereIn('action', AuditCatalog::SENSITIVE))
            ->when(trim((string) ($f['q'] ?? '')), function ($q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                $q->where(fn ($w) => $w->where('summary', 'like', $like)
                    ->orWhere('subject_label', 'like', $like)
                    ->orWhere('actor_name', 'like', $like)
                    ->orWhere('reason', 'like', $like));
            });
    }
}
