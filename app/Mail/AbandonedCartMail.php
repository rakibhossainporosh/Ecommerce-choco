<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        public string $shopName,
        public string $fromAddress,
        public string $fromName,
        public string $subjectLine,
        public string $intro,
        public array $items,
        public string $total,
        public string $recoverUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName),
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.abandoned-cart',
        );
    }
}
