<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Support\OrderSmsSettings;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send a single SMS to a recipient phone number.
     */
    public function send(string $to, string $message): bool
    {
        if (! OrderSmsSettings::enabled()) {
            return false;
        }

        $formattedTo = $this->formatBangladeshiPhone($to);
        if (! $formattedTo) {
            Log::warning("SmsService: Invalid phone number '{$to}'");

            return false;
        }

        $driver = OrderSmsSettings::driver();

        try {
            return match ($driver) {
                'greenweb' => $this->sendViaGreenweb($formattedTo, $message),
                'bulksmsbd' => $this->sendViaBulkSmsBd($formattedTo, $message),
                'custom' => $this->sendViaCustom($formattedTo, $message),
                default => $this->sendViaLog($formattedTo, $message),
            };
        } catch (Exception $e) {
            Log::error("SmsService failed to send SMS to {$formattedTo}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Send order placement confirmation SMS to customer.
     */
    public function sendOrderPlacedSms(Order $order): bool
    {
        return app(OrderSmsNotifier::class)->send($order, 'placed');
    }

    /**
     * Send order status update SMS to customer.
     */
    public function sendOrderStatusUpdatedSms(Order $order, string $statusBengali): bool
    {
        $status = $order->status;
        $step = $status instanceof OrderStatus ? $status->value : (string) $status;

        return app(OrderSmsNotifier::class)->send($order, $step, $statusBengali);
    }

    /**
     * Send registration welcome SMS to newly registered customer.
     */
    public function sendCustomerRegistrationSms(Customer $customer): bool
    {
        return app(OrderSmsNotifier::class)->sendRegistered($customer);
    }

    /**
     * Standardize phone number format for Bangladeshi operators (e.g. 8801XXXXXXXXX).
     */
    public function formatBangladeshiPhone(string $phone): ?string
    {
        $cleaned = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($cleaned, '8801') && strlen($cleaned) === 13) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '01') && strlen($cleaned) === 11) {
            return '88'.$cleaned;
        }

        if (str_starts_with($cleaned, '1') && strlen($cleaned) === 10) {
            return '880'.$cleaned;
        }

        return strlen($cleaned) >= 10 ? $cleaned : null;
    }

    /**
     * Log SMS for local development and testing.
     */
    protected function sendViaLog(string $to, string $message): bool
    {
        Log::info("SMS [LOG DRIVER] to {$to}: {$message}");

        return true;
    }

    /**
     * Send SMS via Greenweb BD API.
     */
    protected function sendViaGreenweb(string $to, string $message): bool
    {
        $token = OrderSmsSettings::credential('greenweb_token');
        if (! $token) {
            Log::warning('SmsService: Greenweb token is missing.');

            return false;
        }

        $response = Http::asForm()->connectTimeout(3)->timeout(10)->post('https://api.greenweb.com.bd/api.php', [
            'token' => $token,
            'to' => $to,
            'message' => $message,
        ]);

        if ($response->successful()) {
            Log::info("SmsService: Greenweb SMS sent successfully to {$to}");

            return true;
        }

        Log::error("SmsService: Greenweb API error: {$response->body()}");

        return false;
    }

    /**
     * Send SMS via BulkSMSBD API.
     */
    protected function sendViaBulkSmsBd(string $to, string $message): bool
    {
        $apiKey = OrderSmsSettings::credential('bulksmsbd_api_key');
        $senderId = OrderSmsSettings::credential('bulksmsbd_sender_id');

        if (! $apiKey) {
            Log::warning('SmsService: BulkSMSBD API key is missing.');

            return false;
        }

        $response = Http::connectTimeout(3)->timeout(10)->post('http://bulksmsbd.net/api/smsapi', [
            'api_key' => $apiKey,
            'type' => 'text',
            'number' => $to,
            'senderid' => $senderId,
            'message' => $message,
        ]);

        if ($response->successful()) {
            Log::info("SmsService: BulkSMSBD SMS sent successfully to {$to}");

            return true;
        }

        Log::error('SmsService: BulkSMSBD API error: status '.$response->status());

        return false;
    }

    /**
     * Send SMS through the shop's own gateway URL.
     */
    protected function sendViaCustom(string $to, string $message): bool
    {
        $url = OrderSmsSettings::customUrl();
        $payload = OrderSmsSettings::customRequest($to, $message);

        if ($url === null || $payload === null || $payload['params'] === []) {
            Log::warning('SmsService: Custom gateway URL or fields are missing.');

            return false;
        }

        $request = Http::connectTimeout(3)->timeout(10);

        if ($payload['headers'] !== []) {
            $request = $request->withHeaders($payload['headers']);
        }

        $params = $payload['params'];
        $response = match (OrderSmsSettings::customMethod()) {
            'get' => $request->get($url, $params),
            'json' => $request->acceptJson()->post($url, $params),
            default => $request->asForm()->post($url, $params),
        };

        if ($response->successful()) {
            Log::info("SmsService: Custom gateway SMS sent successfully to {$to}");

            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);
        Log::error('SmsService: Custom gateway failed for '.(is_string($host) ? $host : 'the SMS company').' with status '.$response->status());

        return false;
    }

    /**
     * @return array{state: 'ready'|'missing'|'unsupported'|'failed', amount: ?string}
     */
    public function balance(bool $fresh = false): array
    {
        $cacheKey = $this->balanceCacheKey();

        if (! $fresh && is_string($cacheKey)) {
            $cached = Cache::get($cacheKey);

            if (is_array($cached) && ($cached['state'] ?? null) === 'ready') {
                return [
                    'state' => 'ready',
                    'amount' => is_string($cached['amount'] ?? null) ? $cached['amount'] : null,
                ];
            }
        }

        $result = $this->fetchBalance();

        if ($result['state'] === 'ready' && is_string($cacheKey)) {
            Cache::put($cacheKey, $result, 60);
        }

        return $result;
    }

    /**
     * @return array{state: 'ready'|'missing'|'unsupported'|'failed', amount: ?string}
     */
    private function fetchBalance(): array
    {
        $driver = OrderSmsSettings::driver();

        return match ($driver) {
            'greenweb' => $this->balanceFromGreenweb(),
            'bulksmsbd' => $this->balanceFromBulkSmsBd(),
            'custom' => $this->balanceFromCustom(),
            default => ['state' => 'missing', 'amount' => null],
        };
    }

    /**
     * @return array{state: 'ready'|'missing'|'unsupported'|'failed', amount: ?string}
     */
    private function balanceFromGreenweb(): array
    {
        $token = OrderSmsSettings::credential('greenweb_token');

        if (! is_string($token) || $token === '') {
            return ['state' => 'missing', 'amount' => null];
        }

        $url = 'https://api.greenweb.com.bd/g_api.php?'.http_build_query([
            'token' => $token,
            'json' => 1,
        ]).'&balance';

        return $this->readBalanceResponse('Greenweb', $url);
    }

    /**
     * @return array{state: 'ready'|'missing'|'unsupported'|'failed', amount: ?string}
     */
    private function balanceFromBulkSmsBd(): array
    {
        $apiKey = OrderSmsSettings::credential('bulksmsbd_api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return ['state' => 'missing', 'amount' => null];
        }

        return $this->readBalanceResponse(
            'BulkSMSBD',
            'https://bulksmsbd.net/api/getBalanceApi?'.http_build_query(['api_key' => $apiKey]),
            requireAcceptedCode: true,
        );
    }

    /**
     * @return array{state: 'ready'|'missing'|'unsupported'|'failed', amount: ?string}
     */
    private function balanceFromCustom(): array
    {
        $url = OrderSmsSettings::customBalanceRequestUrl();

        if ($url === null) {
            return ['state' => 'unsupported', 'amount' => null];
        }

        return $this->readBalanceResponse('Custom gateway', $url);
    }

    /**
     * @return array{state: 'ready'|'failed', amount: ?string}
     */
    private function readBalanceResponse(string $company, string $url, bool $requireAcceptedCode = false): array
    {
        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->connectTimeout(3)
                ->timeout(8)
                ->get($url);
        } catch (ConnectionException) {
            Log::warning("SmsService: {$company} balance check could not connect.");

            return ['state' => 'failed', 'amount' => null];
        } catch (Exception) {
            Log::warning("SmsService: {$company} balance check failed.");

            return ['state' => 'failed', 'amount' => null];
        }

        if (! $response->successful()) {
            Log::warning("SmsService: {$company} balance check failed with status ".$response->status());

            return ['state' => 'failed', 'amount' => null];
        }

        $payload = $response->json();

        if ($requireAcceptedCode) {
            $code = is_array($payload) ? ($payload['response_code'] ?? null) : null;

            if ((string) $code !== '202') {
                Log::warning("SmsService: {$company} balance check was refused.");

                return ['state' => 'failed', 'amount' => null];
            }
        }

        $amount = $this->extractBalance($response->body());

        if ($amount === null) {
            Log::warning("SmsService: {$company} balance check returned no number.");

            return ['state' => 'failed', 'amount' => null];
        }

        return ['state' => 'ready', 'amount' => $amount];
    }

    private function extractBalance(string $body): ?string
    {
        $body = trim($body);

        if ($body === '') {
            return null;
        }

        $json = json_decode($body, true);

        if (is_array($json)) {
            return $this->findBalanceValue($json);
        }

        if (preg_match('/^[\d,]+(?:\.\d+)?$/', $body) === 1) {
            return str_replace(',', '', $body);
        }

        if (preg_match('/balance[^0-9]{0,20}([\d,]+(?:\.\d+)?)/i', $body, $matches) === 1) {
            return str_replace(',', '', $matches[1]);
        }

        return null;
    }

    /**
     * @param  array<mixed>  $data
     */
    private function findBalanceValue(array $data, int $depth = 0): ?string
    {
        foreach (['balance', 'Balance', 'sms_balance', 'credit', 'credits'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }

            if (is_string($value) && preg_match('/^[\d,]+(?:\.\d+)?$/', trim($value)) === 1) {
                return str_replace(',', '', trim($value));
            }
        }

        if ($depth >= 2) {
            return null;
        }

        foreach ($data as $value) {
            if (! is_array($value)) {
                continue;
            }

            $found = $this->findBalanceValue($value, $depth + 1);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function balanceCacheKey(): ?string
    {
        $driver = OrderSmsSettings::driver();
        $secret = match ($driver) {
            'greenweb' => OrderSmsSettings::credential('greenweb_token'),
            'bulksmsbd' => OrderSmsSettings::credential('bulksmsbd_api_key'),
            'custom' => (OrderSmsSettings::credential('sms_custom_api_key') ?? '').'|'.(OrderSmsSettings::customBalanceUrl() ?? ''),
            default => null,
        };

        if (! is_string($secret) || $secret === '' || $secret === '|') {
            return null;
        }

        return 'sms-balance.'.$driver.'.'.hash('sha256', $secret);
    }
}
