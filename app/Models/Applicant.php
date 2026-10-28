<?php

namespace App\Models;

use App\Mail\ApplicantOtpMail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * An intern applicant. Signs in to the intern portal with email and password;
 * on exam day signs in with the application number and PIN like any candidate.
 */
class Applicant extends Authenticatable
{
    use SoftDeletes;

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'submitted' => 'Submitted for review',
        'returned' => 'Returned for correction',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public const OTP_MINUTES = 15;
    public const OTP_MAX_ATTEMPTS = 5;
    public const OTP_RESEND_SECONDS = 60;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'email_otp_hash'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'documents_verified_at' => 'datetime',
            'discipline_locked_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'email_otp_sent_at' => 'datetime',
            'email_otp_expires_at' => 'datetime',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->surname));
    }

    // ---- Relationships ----

    public function intake(): BelongsTo
    {
        return $this->belongsTo(Intake::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'exam_category_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ExamRegistration::class, 'candidate_id')->where('candidate_type', 'applicant');
    }

    // ---- Status ----

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /** The profile can be changed only before review, or after it was returned, and only while the intake is open. */
    public function canEditProfile(): bool
    {
        return in_array($this->status, ['draft', 'returned'], true) && (bool) $this->intake?->isOpen();
    }

    public function profileIsComplete(): bool
    {
        return $this->exam_category_id && $this->phone && $this->passport_path && $this->credential_path;
    }

    // ---- Application number ----

    /** For example INT/2027/0001. Stored in upper case, as the candidate login expects. */
    public static function nextApplicationNumber(Intake $intake): string
    {
        $prefix = 'INT/'.$intake->year.'/';

        $last = static::withTrashed()
            ->where('application_number', 'like', $prefix.'%')
            ->orderByDesc('application_number')
            ->value('application_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // ---- Email one-time code ----

    /** Create a new code, store only its hash, and email it. */
    public function sendEmailOtp(): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_otp_hash' => Hash::make($code),
            'email_otp_sent_at' => now(),
            'email_otp_expires_at' => now()->addMinutes(self::OTP_MINUTES),
            'email_otp_attempts' => 0,
        ])->save();

        Mail::to($this->email)->send(new ApplicantOtpMail($this->first_name, $code, self::OTP_MINUTES));
    }

    public function canResendEmailOtp(): bool
    {
        return ! $this->email_otp_sent_at
            || $this->email_otp_sent_at->copy()->addSeconds(self::OTP_RESEND_SECONDS)->isPast();
    }

    public function resendWaitSeconds(): int
    {
        return $this->email_otp_sent_at
            ? max(0, (int) now()->diffInSeconds($this->email_otp_sent_at->copy()->addSeconds(self::OTP_RESEND_SECONDS), false))
            : 0;
    }

    /**
     * @return 'ok'|'wrong'|'expired'|'locked'
     */
    public function verifyEmailOtp(string $code): string
    {
        if (! $this->email_otp_hash || ! $this->email_otp_expires_at || $this->email_otp_expires_at->isPast()) {
            return 'expired';
        }

        if ($this->email_otp_attempts >= self::OTP_MAX_ATTEMPTS) {
            return 'locked';
        }

        if (! Hash::check($code, $this->email_otp_hash)) {
            $this->increment('email_otp_attempts');

            return $this->email_otp_attempts >= self::OTP_MAX_ATTEMPTS ? 'locked' : 'wrong';
        }

        $this->forceFill([
            'email_verified_at' => now(),
            'email_otp_hash' => null,
            'email_otp_expires_at' => null,
            'email_otp_attempts' => 0,
        ])->save();

        return 'ok';
    }
}
