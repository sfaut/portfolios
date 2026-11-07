<?php

namespace App\Mail;

use App\Models\Portfolio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortfolioDeletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Portfolio $portfolio
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Portfolio archivé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.portfolio.deleted',
        );
    }

    // Return array<int, \Illuminate\Mail\Mailables\Attachment>
    public function attachments(): array
    {
        return [];
    }
}
