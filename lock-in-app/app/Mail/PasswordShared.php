<?php

namespace App\Mail;

use App\Models\SharedEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordShared extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SharedEntry $share,
        public readonly string $shareUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name').' — '.$this->share->owner->name.' shared a password with you',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-shared',
        );
    }
}
