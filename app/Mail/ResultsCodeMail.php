<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResultsCodeMail extends Mailable
{
    public function __construct(public string $name, public string $code, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your ExamSphere results code');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.results-code');
    }
}
