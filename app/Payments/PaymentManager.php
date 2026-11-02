<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\CodGateway;
use App\Payments\Gateways\ManualGateway;
use App\Payments\Gateways\SslCommerzGateway;
use App\Support\PaymentGatewaySettings;

class PaymentManager
{
    /**
     * A new integration is one gateway class, registered here by its key.
     * Checkout and the admin screen follow that key.
     *
     * @var array<string, PaymentGateway>
     */
    private array $registered = [];

    public function __construct()
    {
        $this->register(new CodGateway);
        $this->register(new ManualGateway('bkash', 20));
        $this->register(new ManualGateway('nagad', 30));
        $this->register(new ManualGateway('rocket', 40));
        $this->register(new ManualGateway('bank_transfer', 50));
        $this->register(new SslCommerzGateway);
    }

    public function register(PaymentGateway $gateway): void
    {
        $this->registered[$gateway->key()] = $gateway;
    }

    public function find(string $key): ?PaymentGateway
    {
        return $this->registered[strtolower(trim($key))] ?? null;
    }

    /**
     * @return list<PaymentGateway>
     */
    public function all(): array
    {
        $gateways = array_values($this->registered);
        usort($gateways, fn (PaymentGateway $left, PaymentGateway $right): int => $left->sort() <=> $right->sort());

        return $gateways;
    }

    /**
     * @return list<array{key: string, label: string, description: string, mode: string, account: ?string}>
     */
    public function checkoutOptions(): array
    {
        $options = [];

        foreach ($this->all() as $gateway) {
            if (PaymentGatewaySettings::enabled($gateway) && $gateway->isReady()) {
                $options[] = $gateway->present();
            }
        }

        if ($options === [] && ($cod = $this->find('cod'))) {
            $options[] = $cod->present();
        }

        return $options;
    }

    public function checkoutGateway(string $key): ?PaymentGateway
    {
        $gateway = $this->find($key);

        if (! $gateway) {
            return null;
        }

        if (PaymentGatewaySettings::enabled($gateway) && $gateway->isReady()) {
            return $gateway;
        }

        if ($gateway->key() === 'cod' && $this->enabledReadyCount() === 0) {
            return $gateway;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->all() as $gateway) {
            $options[$gateway->key()] = $gateway->label();
        }

        return $options;
    }

    private function enabledReadyCount(): int
    {
        return count(array_filter(
            $this->all(),
            fn (PaymentGateway $gateway): bool => PaymentGatewaySettings::enabled($gateway) && $gateway->isReady(),
        ));
    }
}
