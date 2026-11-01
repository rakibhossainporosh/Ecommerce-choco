<?php

namespace App\Services;

use App\Mail\OrderUpdateMail;
use App\Mail\StoreTestMail;
use App\Models\Order;
use App\Support\OrderMailSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderMailer
{
    public function send(Order $order, string $step): void
    {
        if (! OrderMailSettings::enabled() || ! array_key_exists($step, OrderMailSettings::steps())) {
            return;
        }

        $config = OrderMailSettings::mailerConfig();
        $from = OrderMailSettings::fromAddress();

        if ($config === null || $from === null) {
            return;
        }

        $rule = OrderMailSettings::rule($step);
        $order->loadMissing(['items', 'customer']);

        if ($rule['customer']) {
            $customerEmail = OrderMailSettings::customerEmail($order);

            if ($customerEmail !== null) {
                $this->deliver($config, $customerEmail, new OrderUpdateMail(
                    order: $order,
                    step: $step,
                    audience: 'customer',
                    fromAddress: $from,
                    fromName: OrderMailSettings::fromName(),
                    replyToAddress: OrderMailSettings::adminEmail(),
                    actionUrl: route('track.order'),
                    actionLabel: __('Track this order'),
                ));
            }
        }

        if ($rule['admin']) {
            $adminEmail = OrderMailSettings::adminEmail();

            if ($adminEmail !== null) {
                $this->deliver($config, $adminEmail, new OrderUpdateMail(
                    order: $order,
                    step: $step,
                    audience: 'admin',
                    fromAddress: $from,
                    fromName: OrderMailSettings::fromName(),
                    replyToAddress: OrderMailSettings::customerEmail($order),
                    actionUrl: route('filament.admin.resources.orders.view', $order),
                    actionLabel: __('View order'),
                ));
            }
        }
    }

    public function sendTest(): bool
    {
        if (! OrderMailSettings::enabled()) {
            return false;
        }

        $config = OrderMailSettings::mailerConfig();
        $from = OrderMailSettings::fromAddress();
        $recipient = OrderMailSettings::adminEmail() ?? $from;

        if ($config === null || $from === null || $recipient === null) {
            return false;
        }

        return $this->deliver($config, $recipient, new StoreTestMail(
            fromAddress: $from,
            fromName: OrderMailSettings::fromName(),
            shopName: OrderMailSettings::fromName(),
        ));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function deliver(array $config, string $recipient, OrderUpdateMail|StoreTestMail $mail): bool
    {
        try {
            config(['mail.mailers.store' => $config]);
            Mail::purge('store');
            Mail::mailer('store')->to($recipient)->send($mail);

            return true;
        } catch (Throwable $exception) {
            Log::error('Order email failed: '.$exception->getMessage());

            return false;
        }
    }
}
