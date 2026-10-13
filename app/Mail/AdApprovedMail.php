<?php

namespace App\Mail;

use App\Models\AdSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public AdSubmission $ad;

    public function __construct(AdSubmission $ad)
    {
        $this->ad = $ad;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Your Advertisement Has Been Approved! - ' . $this->ad->business_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ad_approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
