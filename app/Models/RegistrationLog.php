<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(int $examId, string $action, ?string $detail = null, ?string $reason = null, ?int $registrationId = null, ?int $listId = null): void
    {
        static::create([
            'exam_id' => $examId,
            'action' => $action,
            'detail' => $detail,
            'reason' => $reason,
            'exam_registration_id' => $registrationId,
            'eligible_list_id' => $listId,
            'user_id' => auth()->id(),
        ]);

        [$category, $text] = \App\Support\AuditCatalog::legacy($action);

        \App\Support\Audit::record('reg.'.$action, $category, $text.($detail ? ' · '.$detail : ''), [
            'subject_type' => $registrationId ? 'registration' : null,
            'subject_id' => $registrationId,
            'subject_label' => $detail,
            'exam_id' => $examId,
            'reason' => $reason,
        ]);
    }
}
