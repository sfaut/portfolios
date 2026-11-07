<?php

namespace App\Mail;

use App\Models\Portfolio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Place le mail dans une queue même si send() est utilisé
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortfolioCreatedMail extends Mailable implements ShouldQueue
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
            subject: 'Portfolio créé',
        );
    }

    // https://laravel.com/api/11.x/Illuminate/Mail/Mailables/Content.html
    public function content(): Content
    {
        return new Content(
            view: 'mail.portfolio.created',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
