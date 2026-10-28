<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Exam extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'in_review', 'approved', 'scheduled', 'closed'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pass_mark' => 'decimal:2',
            'negative_mark_fraction' => 'decimal:3',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'forward_only' => 'boolean',
            'approved_at' => 'datetime',
            'results_released_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'exam_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class)->orderBy('position');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    /**
     * For each section: how many questions are set up against how many are needed.
     *
     * @return array<int, array{have:int, need:int, ok:bool, note:?string}>
     */
    public function sectionStatuses(): array
    {
        $this->loadMissing('sections');
        $out = [];

        foreach ($this->sections as $section) {
            $need = (int) $section->question_count;
            $note = null;

            if ($this->paper_mode === 'fixed_set') {
                $rows = $section->fixedQuestions()->with('question:id,status')->get();
                $have = $rows->count();
                $notApproved = $rows->filter(fn ($r) => ! $r->question || $r->question->status !== 'approved')->count();

                if ($notApproved) {
                    $note = "{$notApproved} chosen question(s) are no longer approved. Replace them.";
                } elseif ($have > $need) {
                    $note = "Too many questions chosen ({$have} of {$need}). Remove ".($have - $need).'.';
                } elseif ($have < $need) {
                    $note = "{$have} of {$need} questions chosen.";
                }
            } else {
                $rows = $section->blueprints()->get();
                $have = (int) $rows->sum('question_count');

                $pools = Question::where('status', 'approved')
                    ->whereIn('topic_id', $rows->pluck('topic_id'))
                    ->select('topic_id', DB::raw('count(*) as c'))
                    ->groupBy('topic_id')
                    ->pluck('c', 'topic_id');

                $short = $rows->filter(fn ($r) => ($pools[$r->topic_id] ?? 0) < $r->question_count);

                if ($short->isNotEmpty()) {
                    $names = Topic::whereIn('id', $short->pluck('topic_id'))->pluck('name')->implode(', ');
                    $note = "Not enough approved questions in: {$names}.";
                } elseif ($have > $need) {
                    $note = "The topic counts add up to {$have}, but the section has {$need} questions.";
                } elseif ($have < $need) {
                    $note = "{$have} of {$need} questions planned.";
                }
            }

            $out[$section->id] = ['have' => $have, 'need' => $need, 'ok' => $note === null, 'note' => $note];
        }

        return $out;
    }

    /** Everything that still needs doing before the exam can go for review. Empty means ready. */
    public function readinessIssues(?array $statuses = null): array
    {
        $this->loadMissing('sections');

        if ($this->sections->isEmpty()) {
            return ['The exam has no sections.'];
        }

        $statuses ??= $this->sectionStatuses();
        $issues = [];

        foreach ($this->sections as $section) {
            $status = $statuses[$section->id] ?? null;
            if ($status && ! $status['ok']) {
                $issues[] = $section->title.': '.$status['note'];
            }
        }

        return $issues;
    }

    /** Time the exam takes, from the sections' question counts and timers (before any extra time). */
    public function totalSeconds(): int
    {
        $this->loadMissing('sections');

        return (int) $this->sections->sum(fn ($s) => $s->question_count * $s->seconds_per_question);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
