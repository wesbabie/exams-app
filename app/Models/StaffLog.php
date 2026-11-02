<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public static function record(?int $targetId, string $action, ?string $detail = null): void
    {
        static::create(['actor_id' => auth()->id(), 'target_id' => $targetId, 'action' => $action, 'detail' => $detail]);

        \App\Support\Audit::record('staff.'.$action, 'accounts', \App\Support\AuditCatalog::staff($action).($detail ? ' · '.$detail : ''), [
            'subject_type' => $targetId ? 'user' : null,
            'subject_id' => $targetId,
            'subject_label' => $targetId ? \App\Models\User::whereKey($targetId)->value('name') : null,
        ]);
    }
}
