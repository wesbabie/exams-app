<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

/**
 * A candidate's registration for one exam. Candidates sign in to a registration
 * (exam number + PIN); there is no user account and no custom auth guard.
 */
class ExamRegistration extends Model
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

    // ---- Relationships ----

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class, 'exam_registration_id');
    }

    public function eligibleList(): BelongsTo
    {
        return $this->belongsTo(EligibleList::class, 'eligible_list_id');
    }

    /** The staff candidate or applicant this registration belongs to. */
    protected function candidate(): Attribute
    {
        return Attribute::get(fn () => match ($this->candidate_type) {
            'staff' => StaffCandidate::find($this->candidate_id),
            'applicant' => Applicant::find($this->candidate_id),
            default => null,
        })->shouldCache();
    }

    // ---- Sign-in rules ----

    /**
     * The registration this exam number can sign in to right now:
     * admitted by an invigilator, and the sitting is open.
     */
    public static function findAdmittedFor(string $number): ?self
    {
        return static::query()
            ->whereNotNull('admitted_at')
            ->whereHas('examSession', fn ($q) => $q->where('status', '!=', 'closed')->where('starts_at', '<=', now()))
            // After the sitting ends, only someone who has already started can get back in to finish
            ->where(fn ($q) => $q
                ->whereHas('examSession', fn ($sitting) => $sitting->where('ends_at', '>=', now()))
                ->orWhereHas('attempts', fn ($attempt) => $attempt->where('status', 'in_progress')))
            ->where(function ($q) use ($number) {
                $q->where(fn ($q) => $q->where('candidate_type', 'staff')
                        ->whereIn('candidate_id', StaffCandidate::where('file_number', $number)->select('id')))
                  ->orWhere(fn ($q) => $q->where('candidate_type', 'applicant')
                        ->whereIn('candidate_id', Applicant::where('application_number', $number)->select('id')));
            })
            ->latest('id')
            ->first();
    }

    /** Checked on every request, so an invigilator can lock or withdraw a candidate mid-sitting. */
    public function canStillSignIn(): bool
    {
        if (! $this->admitted_at || $this->locked_at) {
            return false;
        }

        $sitting = $this->examSession;

        if (! $sitting || $sitting->status === 'closed' || $sitting->starts_at->gt(now())) {
            return false;
        }

        // Once the window has passed, only a candidate who is part-way through the exam may continue
        return $sitting->ends_at->gte(now())
            || $this->attempts()->where('status', 'in_progress')->exists();
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
