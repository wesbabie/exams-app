<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResultsReleasedMail extends Mailable
{
    /** @param  string  $howTo  one sentence on how this person opens their result */
    public function __construct(public string $name, public string $examTitle, public string $url, public string $howTo) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your results are ready: '.$this->examTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.results-released');
    }
}
