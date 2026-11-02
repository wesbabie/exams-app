<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes lines to the audit trail. It must never break the action being recorded,
 * so a failure is reported to the log file and swallowed.
 */
final class Audit
{
    /** Never stored, wherever they appear. */
    private const HIDDEN = ['password', 'password_confirmation', 'remember_token', 'pin', 'pin_hash', 'email_otp_hash', '_token', 'current_password'];

    /**
     * @param  array{actor?: ?User, subject_type?: ?string, subject_id?: ?int, subject_label?: ?string, exam_id?: ?int, reason?: ?string, meta?: ?array, ip?: ?string}  $o
     */
    public static function record(string $action, string $category, string $summary, array $o = []): void
    {
        try {
            $actor = array_key_exists('actor', $o) ? $o['actor'] : auth()->user();
            $request = app()->bound('request') ? request() : null;

            AuditLog::create([
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'actor_role' => $actor?->role,
                'category' => $category,
                'action' => $action,
                'summary' => Str::limit($summary, 480, '…'),
                'subject_type' => $o['subject_type'] ?? null,
                'subject_id' => $o['subject_id'] ?? null,
                'subject_label' => isset($o['subject_label']) ? Str::limit($o['subject_label'], 250, '…') : null,
                'exam_id' => $o['exam_id'] ?? null,
                'reason' => $o['reason'] ?? null,
                'meta' => isset($o['meta']) ? self::clean($o['meta']) : null,
                'ip_address' => $o['ip'] ?? $request?->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Drop secrets and cut long values so the log stays readable. */
    public static function clean(array $data, int $depth = 0): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array($key, self::HIDDEN, true)) {
                continue;
            }

            if (is_array($value)) {
                $out[$key] = $depth < 6 ? self::clean($value, $depth + 1) : '…';
            } elseif (is_string($value)) {
                $out[$key] = Str::limit($value, 200, '…');
            } elseif (is_scalar($value) || $value === null) {
                $out[$key] = $value;
            } else {
                $out[$key] = (string) json_encode($value);
            }
        }

        return $out;
    }
}
