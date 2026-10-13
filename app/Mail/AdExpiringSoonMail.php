<?php

namespace App\Mail;

use App\Models\AdSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdExpiringSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public AdSubmission $ad;
    public string $portalUrl;

    public function __construct(AdSubmission $ad)
    {
        $this->ad = $ad;
        $this->portalUrl = url('/advertiser/status/' . ($ad->payment_reference ?: $ad->id));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Your Ad Campaign is Expiring Soon - ' . $this->ad->business_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ad_expiring_soon',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
