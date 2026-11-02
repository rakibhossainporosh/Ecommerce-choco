<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Data\PaymentOutcome;
use App\Payments\Data\PaymentStart;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentGatewayMode;
use App\Support\PaymentGatewaySettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslCommerzGateway extends AbstractGateway
{
    public function key(): string
    {
        return 'sslcommerz';
    }

    public function label(): string
    {
        return $this->line('label');
    }

    public function description(): string
    {
        return $this->line('description');
    }

    public function mode(): PaymentGatewayMode
    {
        return PaymentGatewayMode::Redirect;
    }

    public function sort(): int
    {
        return 60;
    }

    public function settingsFields(): array
    {
        return [
            [
                'key' => 'store_id',
                'type' => 'text',
                'label' => __('Store ID'),
                'helper' => __('The store ID from the SSLCommerz merchant panel.'),
                'max' => 80,
            ],
            [
                'key' => 'store_password',
                'type' => 'secret',
                'label' => __('Store password'),
                'helper' => __('Leave blank to keep the saved password.'),
            ],
            [
                'key' => 'sandbox',
                'type' => 'toggle',
                'label' => __('Sandbox'),
                'helper' => __('Keep this on while testing. Turn it off only for live payments. A localhost shop can receive the browser return. The payment notification reaches the shop only when the site address is a public https domain.'),
                'default' => true,
            ],
        ];
    }

    public function isReady(): bool
    {
        return PaymentGatewaySettings::text($this->key(), 'store_id') !== ''
            && PaymentGatewaySettings::hasSecret($this->key(), 'store_password');
    }

    public function successNotice(Order $order): array
    {
        $payment = $this->latestPayment($order);
        $paid = (bool) $payment?->isCompleted();

        return [
            'mode' => $this->mode()->value,
            'title' => $paid
                ? __('storefront.success.paid_title')
                : __('storefront.success.pending_title'),
            'body' => $paid
                ? __('storefront.success.paid_body')
                : __('storefront.success.pending_body'),
            'account' => null,
            'reference' => $payment?->transaction_id,
            'paid' => $paid,
        ];
    }

    public function start(Order $order, ?string $reference): PaymentStart
    {
        $payment = $order->recordPayment(
            amount: (float) $order->grand_total,
            method: $this->key(),
            status: PaymentTransactionStatus::Pending,
        );

        $payment->transaction_id = $payment->payment_number;
        $payment->metadata = ['gateway' => $this->key()];
        $payment->save();

        try {
            $redirectUrl = $this->openSession($order, $payment);
        } catch (PaymentGatewayException $exception) {
            $payment->markAsFailed($exception->getMessage());

            throw $exception;
        }

        return new PaymentStart($order->order_number, $redirectUrl);
    }

    public function completeReturn(Request $request, string $outcome): PaymentOutcome
    {
        $payment = $this->paymentFromRequest($request);

        if (! $payment) {
            return PaymentOutcome::failed(null, __('storefront.checkout.payment_not_found'));
        }

        $orderNumber = $payment->order?->order_number;

        if ($payment->isCompleted()) {
            return PaymentOutcome::paid($orderNumber, __('storefront.success.paid_body'));
        }

        if ($outcome !== 'success') {
            $this->failQuietly(
                $payment,
                $outcome === 'cancel'
                    ? __('storefront.checkout.payment_cancelled')
                    : __('storefront.checkout.payment_failed'),
            );

            return PaymentOutcome::failed(
                $orderNumber,
                $outcome === 'cancel'
                    ? __('storefront.checkout.payment_cancelled')
                    : __('storefront.checkout.payment_failed'),
            );
        }

        $validationId = trim((string) $request->input('val_id', ''));

        if ($validationId === '') {
            return PaymentOutcome::failed($orderNumber, __('storefront.checkout.payment_unconfirmed'));
        }

        $payload = $this->validateTransaction($payment, $validationId);

        return $this->applyValidation($payment, $payload, $orderNumber);
    }

    public function completeWebhook(Request $request): PaymentOutcome
    {
        return $this->completeReturn($request, 'success');
    }

    private function openSession(Order $order, Payment $payment): string
    {
        $response = $this->postSession($order, $payment);

        if (! $response->successful()) {
            Log::warning('SSLCommerz session request failed.', [
                'order' => $order->order_number,
                'status' => $response->status(),
            ]);

            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        $body = $response->json();
        $url = is_array($body) ? ($body['GatewayPageURL'] ?? null) : null;

        if (! is_array($body) || ($body['status'] ?? null) !== 'SUCCESS' || ! is_string($url) || ! $this->trustedRedirect($url)) {
            Log::warning('SSLCommerz did not return a checkout page.', [
                'order' => $order->order_number,
                'status' => is_array($body) ? ($body['status'] ?? null) : null,
            ]);

            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        $payment->metadata = array_merge($payment->metadata ?? [], [
            'gateway' => $this->key(),
            'sessionkey' => $body['sessionkey'] ?? null,
        ]);
        $payment->save();

        return $url;
    }

    private function postSession(Order $order, Payment $payment): Response
    {
        try {
            return Http::timeout(15)
                ->asForm()
                ->acceptJson()
                ->post($this->endpoint('/gwprocess/v4/api.php'), $this->sessionPayload($order, $payment));
        } catch (ConnectionException) {
            Log::warning('SSLCommerz session could not be reached.', [
                'order' => $order->order_number,
            ]);

            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(Order $order, Payment $payment): array
    {
        $product = $order->items()->value('product_name');
        $productName = is_string($product) && $product !== '' ? mb_substr($product, 0, 50) : 'Order '.$order->order_number;

        return [
            'store_id' => PaymentGatewaySettings::text($this->key(), 'store_id'),
            'store_passwd' => PaymentGatewaySettings::secret($this->key(), 'store_password'),
            'total_amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => 'BDT',
            'tran_id' => $payment->transaction_id,
            'success_url' => route('payments.return', ['gateway' => $this->key(), 'outcome' => 'success']),
            'fail_url' => route('payments.return', ['gateway' => $this->key(), 'outcome' => 'fail']),
            'cancel_url' => route('payments.return', ['gateway' => $this->key(), 'outcome' => 'cancel']),
            'ipn_url' => route('payments.ipn', ['gateway' => $this->key()]),
            'cus_name' => $order->customer_name,
            'cus_email' => $order->customer_email,
            'cus_add1' => mb_substr((string) $order->shipping_address_line, 0, 250),
            'cus_city' => $order->shipping_city,
            'cus_postcode' => $order->shipping_postcode ?: '1000',
            'cus_country' => $order->shipping_country ?: 'Bangladesh',
            'cus_phone' => $order->customer_phone,
            'shipping_method' => 'YES',
            'product_name' => $productName,
            'product_category' => 'general',
            'product_profile' => 'physical-goods',
            'value_a' => $order->order_number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTransaction(Payment $payment, string $validationId): array
    {
        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get($this->endpoint('/validator/api/validationserverAPI.php'), [
                    'val_id' => $validationId,
                    'store_id' => PaymentGatewaySettings::text($this->key(), 'store_id'),
                    'store_passwd' => PaymentGatewaySettings::secret($this->key(), 'store_password'),
                    'v' => 1,
                    'format' => 'json',
                ]);
        } catch (ConnectionException) {
            Log::warning('SSLCommerz validation could not be reached.', [
                'payment' => $payment->payment_number,
            ]);

            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        if (! $response->successful() || ! is_array($response->json())) {
            Log::warning('SSLCommerz validation response was unusable.', [
                'payment' => $payment->payment_number,
                'status' => $response->status(),
            ]);

            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyValidation(Payment $payment, array $payload, ?string $orderNumber): PaymentOutcome
    {
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $currency = strtoupper((string) ($payload['currency_type'] ?? $payload['currency'] ?? ''));
        $amount = $payload['amount'] ?? $payload['currency_amount'] ?? null;
        $transactionId = (string) ($payload['tran_id'] ?? '');

        $confirmed = in_array($status, ['VALID', 'VALIDATED'], true)
            && $currency === 'BDT'
            && $transactionId === (string) $payment->transaction_id
            && $this->sameMoney($payment->amount, $amount);

        if (! $confirmed) {
            Log::warning('SSLCommerz payment did not match the order.', [
                'payment' => $payment->payment_number,
                'status' => $status,
                'currency' => $currency,
            ]);

            if ($status !== '' && ! in_array($status, ['VALID', 'VALIDATED'], true)) {
                $this->failQuietly($payment);
            }

            return PaymentOutcome::failed($orderNumber, __('storefront.checkout.payment_unconfirmed'));
        }

        $this->completeOnlinePayment($payment, [
            'gateway' => $this->key(),
            'val_id' => $payload['val_id'] ?? null,
            'bank_tran_id' => $payload['bank_tran_id'] ?? null,
            'card_type' => $payload['card_type'] ?? null,
            'validation_status' => $payload['status'] ?? null,
        ]);

        return PaymentOutcome::paid($orderNumber, __('storefront.success.paid_body'));
    }

    public function reconcile(Payment $payment): bool
    {
        if ($payment->isCompleted()) {
            return true;
        }

        $transactionId = (string) $payment->transaction_id;

        if ($transactionId === '') {
            return false;
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->acceptJson()
                ->get($this->endpoint('/validator/api/merchantTransIDvalidationAPI.php'), [
                    'tran_id' => $transactionId,
                    'store_id' => PaymentGatewaySettings::text($this->key(), 'store_id'),
                    'store_passwd' => PaymentGatewaySettings::secret($this->key(), 'store_password'),
                    'format' => 'json',
                ]);
        } catch (ConnectionException) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        if (! $response->successful() || ! is_array($response->json())) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        $body = $response->json();
        $element = $body['element'] ?? null;
        $row = is_array($element) && array_is_list($element) ? ($element[0] ?? null) : $element;

        if (! is_array($row)) {
            return false;
        }

        return $this->applyValidation($payment, $row, $payment->order?->order_number)->wasPaid();
    }

    public function refundOnline(Payment $payment, string $reason): void
    {
        $bankTranId = $payment->metadata['bank_tran_id'] ?? null;

        if (! is_string($bankTranId) || $bankTranId === '') {
            throw new PaymentGatewayException(__('This card payment has no bank reference to refund.'));
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->asForm()
                ->acceptJson()
                ->post($this->endpoint('/validator/api/merchantTransIDvalidationAPI.php'), [
                    'bank_tran_id' => $bankTranId,
                    'store_id' => PaymentGatewaySettings::text($this->key(), 'store_id'),
                    'store_passwd' => PaymentGatewaySettings::secret($this->key(), 'store_password'),
                    'refund_amount' => number_format((float) $payment->amount, 2, '.', ''),
                    'refund_remarks' => mb_substr($reason !== '' ? $reason : 'Order refund', 0, 255),
                    'v' => 1,
                    'format' => 'json',
                ]);
        } catch (ConnectionException) {
            throw new PaymentGatewayException(__('The card gateway could not be reached for the refund.'));
        }

        $body = $response->json();
        $status = strtolower((string) (is_array($body) ? ($body['status'] ?? '') : ''));

        if (! $response->successful() || ! in_array($status, ['success', 'processing'], true)) {
            Log::warning('SSLCommerz refund was not accepted.', [
                'payment' => $payment->payment_number,
                'status' => $status,
            ]);

            throw new PaymentGatewayException(__('The card gateway did not accept the refund.'));
        }
    }

    private function paymentFromRequest(Request $request): ?Payment
    {
        $transactionId = trim((string) $request->input('tran_id', ''));

        if ($transactionId === '') {
            return null;
        }

        return Payment::query()->where('transaction_id', $transactionId)->first();
    }

    private function failQuietly(Payment $payment, ?string $reason = null): void
    {
        $this->abandonOnlinePayment($payment, $reason ?? __('storefront.checkout.payment_failed'));
    }

    private function endpoint(string $path): string
    {
        $root = PaymentGatewaySettings::bool($this->key(), 'sandbox', true)
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';

        return $root.$path;
    }

    private function trustedRedirect(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        return $scheme === 'https'
            && is_string($host)
            && in_array(strtolower($host), ['sandbox.sslcommerz.com', 'securepay.sslcommerz.com'], true);
    }
}
