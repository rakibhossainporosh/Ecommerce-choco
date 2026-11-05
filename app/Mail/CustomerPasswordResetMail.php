<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $customer,
        public string $code,
        public string $shopName,
        public bool $isReset = true,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isReset
                ? __('storefront.auth.reset_mail_subject', ['shop' => $this->shopName])
                : __('storefront.auth.otp_mail_subject', ['shop' => $this->shopName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.customer-password-reset',
        );
    }
}
