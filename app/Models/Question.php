<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'single_choice' => 'Single choice',
        'multi_select' => 'Multi-select',
        'true_false' => 'True / false',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'in_review' => 'In review',
        'approved' => 'Approved',
        'retired' => 'Retired',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'review_due_on' => 'date',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'exam_category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    public function nextVersion(): HasOne
    {
        return $this->hasOne(self::class, 'previous_version_id');
    }

    /** Question authors only see their own questions; everyone else with bank access sees all. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->role === 'question_author' ? $query->where('created_by', $user->id) : $query;
    }

    public function isReviewOverdue(): bool
    {
        return $this->status === 'approved' && $this->review_due_on && $this->review_due_on->isPast();
    }
}
