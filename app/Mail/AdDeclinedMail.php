<?php

namespace App\Mail;

use App\Models\AdSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public AdSubmission $ad;
    public ?string $reason;

    public function __construct(AdSubmission $ad, ?string $reason = null)
    {
        $this->ad = $ad;
        $this->reason = $reason ?? $ad->decline_reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notice Regarding Your Advertisement Submission - ' . $this->ad->business_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ad_declined',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
