<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PredictionsEmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public Carbon $expiresAt,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'MolMeDB predictions verification code');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.predictions-email-verification',
            with: ['code' => $this->code, 'expiresAt' => $this->expiresAt],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
