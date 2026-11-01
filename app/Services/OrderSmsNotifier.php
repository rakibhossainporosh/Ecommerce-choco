<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Support\OrderSmsSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderSmsNotifier
{
    public function __construct(private SmsService $sms) {}

    public function send(Order $order, string $step, ?string $statusLabel = null): bool
    {
        if (! OrderSmsSettings::enabled() || ! array_key_exists($step, OrderSmsSettings::steps())) {
            return false;
        }

        $rule = OrderSmsSettings::rule($step);
        $values = $this->orderValues($order, $step, $statusLabel);
        $sent = false;

        if ($rule['customer']) {
            $sent = $this->deliver($order->customer_phone, OrderSmsSettings::message($step, 'customer'), $values) || $sent;
        }

        if ($rule['admin']) {
            foreach (OrderSmsSettings::phones() as $phone) {
                $sent = $this->deliver($phone, OrderSmsSettings::message($step, 'admin'), $values) || $sent;
            }
        }

        return $sent;
    }

    public function sendRegistered(Customer $customer): bool
    {
        if (! OrderSmsSettings::enabled() || ! OrderSmsSettings::rule('registered')['customer']) {
            return false;
        }

        return $this->deliver($customer->phone, OrderSmsSettings::message('registered', 'customer'), [
            'name' => (string) $customer->name,
            'phone' => (string) $customer->phone,
            'number' => '',
            'total' => '',
            'status' => '',
            'address' => '',
            'url' => url('/'),
            'shop' => OrderSmsSettings::shopName(),
        ]);
    }

    public function sendTest(string $phone): bool
    {
        if (! OrderSmsSettings::enabled() || ! OrderSmsSettings::gatewayReady()) {
            return false;
        }

        $shop = OrderSmsSettings::shopName();
        $template = app()->getLocale() === 'bn'
            ? '{shop} থেকে একটা টেস্ট এসএমএস।'
            : 'This is a test SMS from {shop}.';

        return $this->deliver($phone, $template, [
            'name' => '',
            'phone' => $phone,
            'number' => '',
            'total' => '',
            'status' => '',
            'address' => '',
            'url' => url('/'),
            'shop' => $shop,
        ]);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function deliver(?string $phone, string $template, array $values): bool
    {
        if (! is_string($phone) || trim($phone) === '') {
            return false;
        }

        $message = OrderSmsSettings::render($template, $values);

        if ($message === '') {
            return false;
        }

        try {
            return $this->sms->send($phone, $message);
        } catch (Throwable $exception) {
            Log::error('SMS failed: '.$exception->getMessage());

            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function orderValues(Order $order, string $step, ?string $statusLabel): array
    {
        $status = $order->status;
        $statusValue = $status instanceof OrderStatus ? $status->value : (string) $status;

        return [
            'name' => (string) $order->customer_name,
            'phone' => (string) $order->customer_phone,
            'number' => (string) $order->order_number,
            'total' => number_format((float) $order->grand_total),
            'status' => filled($statusLabel) ? $statusLabel : $this->statusLabel($step, $statusValue),
            'address' => (string) ($order->shipping_address_line ?? ''),
            'url' => route('track.order', ['order_number' => $order->order_number]),
            'shop' => OrderSmsSettings::shopName(),
        ];
    }

    private function statusLabel(string $step, string $statusValue): string
    {
        if ($step === 'placed') {
            $label = __('Order placed');

            return is_string($label) ? $label : 'Order placed';
        }

        $label = __('enums.order_status.'.$statusValue);

        return is_string($label) ? $label : $statusValue;
    }
}
