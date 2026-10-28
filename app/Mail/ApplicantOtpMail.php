<?php declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ApplicantOtpMail extends Mailable
{
    public function __construct(public string $name, public string $code, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your internship verification code');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.applicant-otp');
    }
}
