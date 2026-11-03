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

class BkashCheckoutGateway extends AbstractGateway
{
    public function key(): string
    {
        return 'bkash_checkout';
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
        return 70;
    }

    public function settingsFields(): array
    {
        return [
            [
                'key' => 'app_key',
                'type' => 'text',
                'label' => __('App key'),
                'helper' => __('The app key from the bKash merchant panel.'),
                'max' => 120,
            ],
            [
                'key' => 'app_secret',
                'type' => 'secret',
                'label' => __('App secret'),
                'helper' => __('Leave blank to keep the saved app secret.'),
            ],
            [
                'key' => 'username',
                'type' => 'text',
                'label' => __('Username'),
                'helper' => __('The username bKash gave this merchant.'),
                'max' => 120,
            ],
            [
                'key' => 'password',
                'type' => 'secret',
                'label' => __('Password'),
                'helper' => __('Leave blank to keep the saved password.'),
            ],
            [
                'key' => 'sandbox',
                'type' => 'toggle',
                'label' => __('Sandbox'),
                'helper' => __('Keep this on while testing. Turn it off only for live bKash payments.'),
                'default' => true,
            ],
        ];
    }

    public function isReady(): bool
    {
        return PaymentGatewaySettings::text($this->key(), 'app_key') !== ''
            && PaymentGatewaySettings::text($this->key(), 'username') !== ''
            && PaymentGatewaySettings::hasSecret($this->key(), 'app_secret')
            && PaymentGatewaySettings::hasSecret($this->key(), 'password');
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
            $token = $this->grantToken();
            $created = $this->createPayment($order, $payment, $token);
        } catch (PaymentGatewayException $exception) {
            $payment->markAsFailed($exception->getMessage());

            throw $exception;
        }

        $payment->transaction_id = $created['paymentID'];
        $payment->metadata = array_merge($payment->metadata ?? [], [
            'gateway' => $this->key(),
            'payment_id' => $created['paymentID'],
        ]);
        $payment->save();

        return new PaymentStart($order->order_number, $created['bkashURL']);
    }

    public function completeReturn(Request $request, string $outcome): PaymentOutcome
    {
        $paymentId = trim((string) $request->input('paymentID', ''));
        $payment = $paymentId !== ''
            ? Payment::query()->where('transaction_id', $paymentId)->first()
            : null;

        if (! $payment) {
            return PaymentOutcome::failed(null, __('storefront.checkout.payment_not_found'));
        }

        $orderNumber = $payment->order?->order_number;
        $status = strtolower(trim((string) $request->input('status', $outcome)));

        if ($payment->isCompleted()) {
            return PaymentOutcome::paid($orderNumber, __('storefront.success.paid_body'));
        }

        if (in_array($status, ['failure', 'fail', 'cancel', 'cancelled'], true)) {
            $this->abandonOnlinePayment(
                $payment,
                $status === 'cancel' || $status === 'cancelled'
                    ? __('storefront.checkout.payment_cancelled')
                    : __('storefront.checkout.payment_failed'),
            );

            return PaymentOutcome::failed(
                $orderNumber,
                $status === 'cancel' || $status === 'cancelled'
                    ? __('storefront.checkout.payment_cancelled')
                    : __('storefront.checkout.payment_failed'),
            );
        }

        $payload = $this->executeOrQuery($paymentId);
        $confirmed = ($payload['statusCode'] ?? null) === '0000'
            && strcasecmp((string) ($payload['transactionStatus'] ?? ''), 'Completed') === 0
            && strtoupper((string) ($payload['currency'] ?? '')) === 'BDT'
            && (string) ($payload['merchantInvoiceNumber'] ?? '') === (string) $payment->payment_number
            && $this->sameMoney($payment->amount, $payload['amount'] ?? null);

        if (! $confirmed) {
            Log::warning('bKash payment did not match the order.', [
                'payment' => $payment->payment_number,
                'status' => $payload['transactionStatus'] ?? null,
            ]);

            if (filled($payload['transactionStatus'] ?? null) && strcasecmp((string) $payload['transactionStatus'], 'Completed') !== 0) {
                $this->abandonOnlinePayment($payment, __('storefront.checkout.payment_failed'));
            }

            return PaymentOutcome::failed($orderNumber, __('storefront.checkout.payment_unconfirmed'));
        }

        $this->completeOnlinePayment($payment, [
            'gateway' => $this->key(),
            'payment_id' => $payload['paymentID'] ?? $paymentId,
            'trx_id' => $payload['trxID'] ?? null,
            'validation_status' => $payload['transactionStatus'] ?? null,
        ]);

        return PaymentOutcome::paid($orderNumber, __('storefront.success.paid_body'));
    }

    public function reconcile(Payment $payment): bool
    {
        if ($payment->isCompleted()) {
            return true;
        }

        $paymentId = (string) ($payment->metadata['payment_id'] ?? $payment->transaction_id);

        if ($paymentId === '' || $paymentId === $payment->payment_number) {
            return false;
        }

        $token = $this->grantToken();
        $response = $this->post('/tokenized/checkout/payment/status', $token, ['paymentID' => $paymentId]);
        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        $confirmed = ($payload['statusCode'] ?? null) === '0000'
            && strcasecmp((string) ($payload['transactionStatus'] ?? ''), 'Completed') === 0
            && strtoupper((string) ($payload['currency'] ?? '')) === 'BDT'
            && (string) ($payload['merchantInvoiceNumber'] ?? '') === (string) $payment->payment_number
            && $this->sameMoney($payment->amount, $payload['amount'] ?? null);

        if (! $confirmed) {
            return false;
        }

        $this->completeOnlinePayment($payment, [
            'gateway' => $this->key(),
            'payment_id' => $payload['paymentID'] ?? $paymentId,
            'trx_id' => $payload['trxID'] ?? null,
            'validation_status' => $payload['transactionStatus'] ?? null,
        ]);

        return true;
    }

    public function refundOnline(Payment $payment, string $reason): void
    {
        $paymentId = $payment->metadata['payment_id'] ?? $payment->transaction_id;
        $trxId = $payment->metadata['trx_id'] ?? null;

        if (! is_string($paymentId) || $paymentId === '' || ! is_string($trxId) || $trxId === '') {
            throw new PaymentGatewayException(__('This bKash payment has no transaction reference to refund.'));
        }

        $refundAmount = (float) ($payment->metadata['refund_amount_override'] ?? $payment->amount);

        $token = $this->grantToken();
        $response = $this->post('/tokenized/checkout/payment/refund', $token, [
            'paymentID' => $paymentId,
            'trxID' => $trxId,
            'amount' => number_format($refundAmount, 2, '.', ''),
            'sku' => 'order',
            'reason' => mb_substr($reason !== '' ? $reason : 'Order refund', 0, 255),
        ]);
        $body = $response->json();

        if (! $response->successful() || ! is_array($body) || ($body['statusCode'] ?? null) !== '0000') {
            Log::warning('bKash refund was not accepted.', [
                'payment' => $payment->payment_number,
                'status' => is_array($body) ? ($body['statusCode'] ?? null) : null,
            ]);

            throw new PaymentGatewayException(__('bKash did not accept the refund.'));
        }
    }

    private function grantToken(): string
    {
        try {
            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'username' => PaymentGatewaySettings::text($this->key(), 'username'),
                    'password' => (string) PaymentGatewaySettings::secret($this->key(), 'password'),
                ])
                ->post($this->endpoint('/tokenized/checkout/token/grant'), [
                    'app_key' => PaymentGatewaySettings::text($this->key(), 'app_key'),
                    'app_secret' => PaymentGatewaySettings::secret($this->key(), 'app_secret'),
                ]);
        } catch (ConnectionException) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        $token = is_array($response->json()) ? ($response->json()['id_token'] ?? null) : null;

        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        return $token;
    }

    /**
     * @return array{paymentID: string, bkashURL: string}
     */
    private function createPayment(Order $order, Payment $payment, string $token): array
    {
        $phone = preg_replace('/\D+/', '', (string) $order->customer_phone) ?: '01700000000';
        $response = $this->post('/tokenized/checkout/create', $token, [
            'mode' => '0011',
            'payerReference' => mb_substr($phone, 0, 11),
            'callbackURL' => route('payments.return', ['gateway' => $this->key(), 'outcome' => 'success']),
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $payment->payment_number,
        ]);
        $body = $response->json();
        $url = is_array($body) ? ($body['bkashURL'] ?? null) : null;
        $paymentId = is_array($body) ? ($body['paymentID'] ?? null) : null;

        if (! $response->successful() || ! is_array($body) || ($body['statusCode'] ?? null) !== '0000' || ! is_string($url) || ! is_string($paymentId) || ! $this->trustedRedirect($url)) {
            Log::warning('bKash did not return a checkout page.', [
                'order' => $order->order_number,
                'status' => is_array($body) ? ($body['statusCode'] ?? null) : null,
            ]);

            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        return ['paymentID' => $paymentId, 'bkashURL' => $url];
    }

    /**
     * @return array<string, mixed>
     */
    private function executeOrQuery(string $paymentId): array
    {
        try {
            $token = $this->grantToken();
            $response = $this->post('/tokenized/checkout/execute', $token, ['paymentID' => $paymentId]);
        } catch (PaymentGatewayException) {
            $token = $this->grantToken();
            $response = $this->post('/tokenized/checkout/payment/status', $token, ['paymentID' => $paymentId]);
        }

        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $path, string $token, array $payload): Response
    {
        try {
            return Http::connectTimeout(5)
                ->timeout(20)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'Authorization' => $token,
                    'X-App-Key' => PaymentGatewaySettings::text($this->key(), 'app_key'),
                ])
                ->post($this->endpoint($path), $payload);
        } catch (ConnectionException) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }
    }

    private function endpoint(string $path): string
    {
        $root = PaymentGatewaySettings::bool($this->key(), 'sandbox', true)
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';

        return $root.$path;
    }

    private function trustedRedirect(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        return $scheme === 'https'
            && is_string($host)
            && in_array(strtolower($host), ['tokenized.sandbox.bka.sh', 'tokenized.pay.bka.sh'], true);
    }
}
