<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $step,
        public string $audience,
        public string $fromAddress,
        public string $fromName,
        public ?string $replyToAddress,
        public string $actionUrl,
        public string $actionLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName),
            replyTo: filled($this->replyToAddress) ? [new Address($this->replyToAddress)] : [],
            subject: __('mail.subject.'.$this->audience.'.'.$this->step, [
                'number' => $this->order->order_number,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-update',
        );
    }
}
