<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * A candidate's registration for one exam. This is also the authenticatable
 * model for the "candidate" guard: a candidate signs in to a registration, not to a user account.
 */
class ExamRegistration extends Authenticatable
{
    protected $guarded = [];

    protected $hidden = ['pin_hash'];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'admitted_at' => 'datetime',
            'pin_issued_at' => 'datetime',
            'pin_expires_at' => 'datetime',
            'locked_at' => 'datetime',
            'extra_time_multiplier' => 'decimal:2',
        ];
    }

    // ---- Auth plumbing (PIN instead of password, no remember-me) ----
    public function getAuthPassword(): ?string
    {
        return $this->pin_hash;
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    // ---- Relationships ----
    public function candidate(): MorphTo
    {
        return $this->morphTo();
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    // ---- Login rules ----

    /**
     * The registration this exam number can sign in to right now:
     * admitted by an invigilator, and the sitting is open.
     */
    public static function findAdmittedFor(string $number): ?self
    {
        return static::query()
            ->whereNotNull('admitted_at')
            ->whereHas('examSession', fn ($q) => $q
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->where('status', '!=', 'closed'))
            ->where(function ($q) use ($number) {
                $q->where(fn ($q) => $q->where('candidate_type', 'staff')
                        ->whereIn('candidate_id', StaffCandidate::where('file_number', $number)->select('id')))
                  ->orWhere(fn ($q) => $q->where('candidate_type', 'applicant')
                        ->whereIn('candidate_id', Applicant::where('application_number', $number)->select('id')));
            })
            ->latest('id')
            ->first();
    }

    public function pinIsValid(string $pin): bool
    {
        if (! $this->pin_hash) {
            return false;
        }
        if ($this->pin_expires_at && $this->pin_expires_at->isPast()) {
            return false;
        }

        return Hash::check($pin, $this->pin_hash);
    }

    /** Returns true if this failure locked the account. */
    public function recordFailedPin(int $maxAttempts): bool
    {
        $this->increment('pin_failed_attempts');

        if ($this->pin_failed_attempts >= $maxAttempts) {
            $this->forceFill(['locked_at' => now()])->save();

            return true;
        }

        return false;
    }

    public function resetPinAttempts(): void
    {
        $this->forceFill(['pin_failed_attempts' => 0])->save();
    }

    public function unlock(): void
    {
        $this->forceFill(['locked_at' => null, 'pin_failed_attempts' => 0])->save();
    }

    /**
     * Generate a random 6-digit PIN, store only its hash, and return the plain PIN
     * once so it can be printed on the slip at check-in.
     */
    public function issuePin(): string
    {
        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'pin_hash' => Hash::make($pin),
            'pin_issued_at' => now(),
            'pin_expires_at' => $this->examSession?->ends_at?->copy()->addHour(),
            'pin_failed_attempts' => 0,
            'locked_at' => null,
        ])->save();

        return $pin;
    }
}
