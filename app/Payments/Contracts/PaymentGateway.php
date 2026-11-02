<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Payments\Data\PaymentOutcome;
use App\Payments\Data\PaymentStart;
use App\Payments\PaymentGatewayMode;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    public function description(): string;

    public function mode(): PaymentGatewayMode;

    public function sort(): int;

    /**
     * Fields the admin must fill in before this method can be offered.
     *
     * @return list<array{key: string, type: string, label: string, helper: string, max?: int}>
     */
    public function settingsFields(): array;

    public function isReady(): bool;

    /**
     * Checkout card. Secrets never leave this method.
     *
     * @return array{key: string, label: string, description: string, mode: string, account: ?string}
     */
    public function present(): array;

    /**
     * Receipt shown after the order exists.
     *
     * @return array{mode: string, title: string, body: string, account: ?string, reference: ?string, paid: bool}
     */
    public function successNotice(Order $order): array;

    public function start(Order $order, ?string $reference): PaymentStart;

    public function completeReturn(Request $request, string $outcome): PaymentOutcome;

    public function completeWebhook(Request $request): PaymentOutcome;
}
