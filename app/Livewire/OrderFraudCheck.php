<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\FraudCheckService;
use App\Support\FraudCheckSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrderFraudCheck extends Component
{
    public int $orderId;

    /**
     * @var array{state: string, total: int, delivered: int, cancelled: int, level: string}|null
     */
    public ?array $courier = null;

    public function check(FraudCheckService $fraud): void
    {
        abort_unless(auth()->user()?->can('orders.view'), 403);

        $order = Order::query()->find($this->orderId);

        if ($order === null) {
            return;
        }

        $this->courier = $fraud->courier($order->customer_phone);
    }

    public function render(FraudCheckService $fraud): View
    {
        $order = Order::query()->find($this->orderId);
        $shop = $order === null
            ? ['phone' => null, 'orders' => 0, 'delivered' => 0, 'cancelled' => 0, 'returned' => 0, 'level' => 'unknown']
            : $fraud->shop($order);

        $level = $this->courier === null
            ? $shop['level']
            : $fraud->worse($shop['level'], $this->courier['level']);

        return view('livewire.order-fraud-check', [
            'shop' => $shop,
            'level' => $level,
            'company' => FraudCheckSettings::companyName(),
        ]);
    }
}
