<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusAlert extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $step,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, order_number: string, step: string}
     */
    public function toArray(object $notifiable): array
    {
        $status = is_object($this->order->status)
            ? $this->order->status->value
            : (string) $this->order->status;

        $title = __('storefront.account.order_alert_title', [
            'number' => $this->order->order_number,
        ]);

        $body = __('storefront.account.order_alert_body', [
            'number' => $this->order->order_number,
            'status' => __('storefront.account.status_'.$status),
        ]);

        return [
            'title' => $title,
            'body' => $body,
            'order_number' => $this->order->order_number,
            'step' => $this->step,
        ];
    }
}
