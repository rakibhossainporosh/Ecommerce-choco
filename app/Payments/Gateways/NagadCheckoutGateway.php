<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Data\PaymentOutcome;
use App\Payments\Data\PaymentStart;
use App\Payments\NagadSigner;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentGatewayMode;
use App\Support\PaymentGatewaySettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NagadCheckoutGateway extends AbstractGateway
{
    public function key(): string
    {
        return 'nagad_checkout';
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
        return 80;
    }

    public function settingsFields(): array
    {
        return [
            [
                'key' => 'merchant_id',
                'type' => 'text',
                'label' => __('Merchant ID'),
                'helper' => __('The merchant ID from the Nagad merchant panel.'),
                'max' => 40,
            ],
            [
                'key' => 'merchant_number',
                'type' => 'text',
                'label' => __('Merchant number'),
                'helper' => __('The Nagad wallet number registered for this merchant.'),
                'placeholder' => '01XXXXXXXXX',
                'max' => 20,
            ],
            [
                'key' => 'public_key',
                'type' => 'textarea',
                'secret' => true,
                'label' => __('Nagad public key'),
                'helper' => __('Paste the Nagad payment public key. Leave blank to keep the saved key.'),
                'max' => 8000,
            ],
            [
                'key' => 'private_key',
                'type' => 'textarea',
                'secret' => true,
                'label' => __('Merchant private key'),
                'helper' => __('Paste this shop\'s Nagad private key. Leave blank to keep the saved key.'),
                'max' => 8000,
            ],
            [
                'key' => 'sandbox',
                'type' => 'toggle',
                'label' => __('Sandbox'),
                'helper' => __('Keep this on while testing. Turn it off only for live Nagad payments. Set the Nagad callback to this shop\'s payment return address.'),
                'default' => true,
            ],
        ];
    }

    public function isReady(): bool
    {
        return PaymentGatewaySettings::text($this->key(), 'merchant_id') !== ''
            && PaymentGatewaySettings::text($this->key(), 'merchant_number') !== ''
            && PaymentGatewaySettings::hasSecret($this->key(), 'public_key')
            && PaymentGatewaySettings::hasSecret($this->key(), 'private_key');
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
        $orderId = preg_replace('/[^A-Za-z0-9]/', '', $payment->payment_number) ?: $payment->payment_number;
        $payment->transaction_id = $orderId;
        $payment->metadata = ['gateway' => $this->key(), 'order_id' => $orderId];
        $payment->save();

        try {
            $initialized = $this->initialize($orderId);
            $redirectUrl = $this->complete($order, $payment, $orderId, $initialized);
        } catch (PaymentGatewayException $exception) {
            $payment->markAsFailed($exception->getMessage());

            throw $exception;
        }

        $payment->metadata = array_merge($payment->metadata ?? [], [
            'gateway' => $this->key(),
            'order_id' => $orderId,
            'payment_reference' => $initialized['paymentReferenceId'],
        ]);
        $payment->save();

        return new PaymentStart($order->order_number, $redirectUrl);
    }

    public function completeReturn(Request $request, string $outcome): PaymentOutcome
    {
        $orderId = trim((string) $request->input('order_id', ''));
        $payment = $orderId !== ''
            ? Payment::query()->where('transaction_id', $orderId)->first()
            : null;

        if (! $payment) {
            return PaymentOutcome::failed(null, __('storefront.checkout.payment_not_found'));
        }

        $orderNumber = $payment->order?->order_number;
        $status = strtolower(trim((string) $request->input('status', $outcome)));

        if ($payment->isCompleted()) {
            return PaymentOutcome::paid($orderNumber, __('storefront.success.paid_body'));
        }

        if (in_array($status, ['aborted', 'failed', 'cancelled', 'canceled', 'cancel', 'fail'], true)) {
            $this->abandonOnlinePayment($payment, __('storefront.checkout.payment_cancelled'));

            return PaymentOutcome::failed($orderNumber, __('storefront.checkout.payment_cancelled'));
        }

        $reference = trim((string) ($request->input('payment_ref_id') ?: ($payment->metadata['payment_reference'] ?? '')));

        if ($reference === '') {
            return PaymentOutcome::failed($orderNumber, __('storefront.checkout.payment_unconfirmed'));
        }

        $payload = $this->verify($reference);
        $confirmed = strcasecmp((string) ($payload['status'] ?? ''), 'Success') === 0
            && (string) ($payload['orderId'] ?? '') === (string) $payment->transaction_id
            && $this->sameMoney($payment->amount, $payload['amount'] ?? null);

        if (! $confirmed) {
            Log::warning('Nagad payment did not match the order.', [
                'payment' => $payment->payment_number,
                'status' => $payload['status'] ?? null,
            ]);

            if (filled($payload['status'] ?? null) && strcasecmp((string) $payload['status'], 'Success') !== 0) {
                $this->abandonOnlinePayment($payment, __('storefront.checkout.payment_failed'));
            }

            return PaymentOutcome::failed($orderNumber, __('storefront.checkout.payment_unconfirmed'));
        }

        $this->completeOnlinePayment($payment, [
            'gateway' => $this->key(),
            'payment_reference' => $reference,
            'issuer_payment_ref' => $payload['issuerPaymentRefNo'] ?? null,
            'validation_status' => $payload['status'] ?? null,
        ]);

        return PaymentOutcome::paid($orderNumber, __('storefront.success.paid_body'));
    }

    public function reconcile(Payment $payment): bool
    {
        if ($payment->isCompleted()) {
            return true;
        }

        $reference = $payment->metadata['payment_reference'] ?? null;

        if (! is_string($reference) || $reference === '') {
            return false;
        }

        $payload = $this->verify($reference);
        $confirmed = strcasecmp((string) ($payload['status'] ?? ''), 'Success') === 0
            && (string) ($payload['orderId'] ?? '') === (string) $payment->transaction_id
            && $this->sameMoney($payment->amount, $payload['amount'] ?? null);

        if (! $confirmed) {
            return false;
        }

        $this->completeOnlinePayment($payment, [
            'gateway' => $this->key(),
            'payment_reference' => $reference,
            'issuer_payment_ref' => $payload['issuerPaymentRefNo'] ?? null,
            'validation_status' => $payload['status'] ?? null,
        ]);

        return true;
    }

    public function refundOnline(Payment $payment, string $reason): void
    {
        $reference = $payment->metadata['payment_reference'] ?? null;
        $orderId = (string) $payment->transaction_id;

        if (! is_string($reference) || $reference === '' || $orderId === '') {
            throw new PaymentGatewayException(__('This Nagad payment has no reference to refund.'));
        }

        $plain = json_encode([
            'merchantId' => PaymentGatewaySettings::text($this->key(), 'merchant_id'),
            'orderId' => $orderId,
            'paymentRefId' => $reference,
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'referenceMessage' => mb_substr($reason !== '' ? $reason : 'Order refund', 0, 120),
        ], JSON_THROW_ON_ERROR);

        $response = $this->post('/purchase/refund', [
            'sensitiveData' => NagadSigner::encrypt($plain, (string) PaymentGatewaySettings::secret($this->key(), 'public_key')),
            'signature' => NagadSigner::sign($plain, (string) PaymentGatewaySettings::secret($this->key(), 'private_key')),
        ]);
        $body = $this->readSigned($response);
        $status = strtolower((string) ($body['status'] ?? ''));

        if (! in_array($status, ['success', 'refunded'], true)) {
            throw new PaymentGatewayException(__('Nagad did not accept the refund.'));
        }
    }

    /**
     * @return array{paymentReferenceId: string, challenge: string}
     */
    private function initialize(string $orderId): array
    {
        $dateTime = now()->format('YmdHis');
        $plain = json_encode([
            'merchantId' => PaymentGatewaySettings::text($this->key(), 'merchant_id'),
            'datetime' => $dateTime,
            'orderId' => $orderId,
            'challenge' => bin2hex(random_bytes(16)),
        ], JSON_THROW_ON_ERROR);

        $merchantId = rawurlencode(PaymentGatewaySettings::text($this->key(), 'merchant_id'));
        $response = $this->post('/check-out/initialize/'.$merchantId.'/'.rawurlencode($orderId), [
            'accountNumber' => PaymentGatewaySettings::text($this->key(), 'merchant_number'),
            'dateTime' => $dateTime,
            'sensitiveData' => NagadSigner::encrypt($plain, (string) PaymentGatewaySettings::secret($this->key(), 'public_key')),
            'signature' => NagadSigner::sign($plain, (string) PaymentGatewaySettings::secret($this->key(), 'private_key')),
        ]);
        $body = $this->readSigned($response);
        $paymentReferenceId = $body['paymentReferenceId'] ?? null;
        $challenge = $body['challenge'] ?? null;

        if (! is_string($paymentReferenceId) || $paymentReferenceId === '' || ! is_string($challenge) || $challenge === '') {
            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        return ['paymentReferenceId' => $paymentReferenceId, 'challenge' => $challenge];
    }

    /**
     * @param  array{paymentReferenceId: string, challenge: string}  $initialized
     */
    private function complete(Order $order, Payment $payment, string $orderId, array $initialized): string
    {
        $plain = json_encode([
            'merchantId' => PaymentGatewaySettings::text($this->key(), 'merchant_id'),
            'orderId' => $orderId,
            'currencyCode' => '050',
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'challenge' => $initialized['challenge'],
        ], JSON_THROW_ON_ERROR);

        $response = $this->post('/check-out/complete/'.rawurlencode($initialized['paymentReferenceId']), [
            'sensitiveData' => NagadSigner::encrypt($plain, (string) PaymentGatewaySettings::secret($this->key(), 'public_key')),
            'signature' => NagadSigner::sign($plain, (string) PaymentGatewaySettings::secret($this->key(), 'private_key')),
        ]);
        $body = $this->readSigned($response);
        $url = $body['callBackUrl'] ?? null;

        if (! is_string($url) || ! $this->trustedRedirect($url)) {
            Log::warning('Nagad did not return a checkout page.', [
                'order' => $order->order_number,
            ]);

            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }

        return $url;
    }

    /**
     * @return array<string, mixed>
     */
    private function verify(string $paymentReference): array
    {
        try {
            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->acceptJson()
                ->withHeaders($this->headers())
                ->get($this->endpoint('/verify/payment/'.rawurlencode($paymentReference)));
        } catch (ConnectionException) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
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
    private function post(string $path, array $payload): Response
    {
        try {
            return Http::connectTimeout(5)
                ->timeout(20)
                ->acceptJson()
                ->asJson()
                ->withHeaders($this->headers())
                ->post($this->endpoint($path), $payload);
        } catch (ConnectionException) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_start_failed'));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readSigned(Response $response): array
    {
        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        if (! isset($body['sensitiveData'])) {
            return $body;
        }

        $plain = NagadSigner::decrypt((string) $body['sensitiveData'], (string) PaymentGatewaySettings::secret($this->key(), 'private_key'));
        $signature = (string) ($body['signature'] ?? '');

        if ($signature !== '' && ! NagadSigner::verify($plain, $signature, (string) PaymentGatewaySettings::secret($this->key(), 'public_key'))) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        $decoded = json_decode($plain, true);

        if (! is_array($decoded)) {
            throw new PaymentGatewayException(__('storefront.checkout.payment_unconfirmed'));
        }

        return $decoded;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'X-KM-Api-Version' => 'v-0.2.0',
            'X-KM-IP-V4' => request()->ip() ?: '127.0.0.1',
            'X-KM-Client-Type' => 'PC_WEB',
        ];
    }

    private function endpoint(string $path): string
    {
        $root = PaymentGatewaySettings::bool($this->key(), 'sandbox', true)
            ? 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs'
            : 'https://api.mynagad.com/api/dfs';

        return $root.$path;
    }

    private function trustedRedirect(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $sandbox = PaymentGatewaySettings::bool($this->key(), 'sandbox', true);

        if ($sandbox && $host === 'sandbox.mynagad.com') {
            return in_array($scheme, ['http', 'https'], true);
        }

        return $scheme === 'https' && $host === 'api.mynagad.com';
    }
}
