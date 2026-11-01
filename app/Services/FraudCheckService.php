<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\FraudCheckSettings;
use App\Support\OrderSmsSettings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FraudCheckService
{
    /**
     * @return array{phone: ?string, orders: int, delivered: int, cancelled: int, returned: int, level: string}
     */
    public function shop(Order $order): array
    {
        $phone = OrderSmsSettings::canonicalPhone((string) $order->customer_phone);
        $empty = [
            'phone' => $phone,
            'orders' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'returned' => 0,
            'level' => 'unknown',
        ];

        if ($phone === null) {
            return $empty;
        }

        $counts = Order::query()
            ->whereKeyNot($order->id)
            ->whereIn('customer_phone', [$phone, '88'.$phone, '+88'.$phone])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $delivered = (int) ($counts[OrderStatus::Delivered->value] ?? 0);
        $cancelled = (int) ($counts[OrderStatus::Cancelled->value] ?? 0);
        $returned = (int) ($counts[OrderStatus::Returned->value] ?? 0) + (int) ($counts[OrderStatus::Refunded->value] ?? 0);
        $orders = (int) $counts->sum();

        return [
            'phone' => $phone,
            'orders' => $orders,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'returned' => $returned,
            'level' => $this->level($delivered, $cancelled, $returned, $orders),
        ];
    }

    /**
     * @return array{state: string, total: int, delivered: int, cancelled: int, level: string}
     */
    public function courier(?string $phone): array
    {
        $empty = ['state' => 'missing', 'total' => 0, 'delivered' => 0, 'cancelled' => 0, 'level' => 'unknown'];

        if (! FraudCheckSettings::ready()) {
            return $empty;
        }

        $canonical = OrderSmsSettings::canonicalPhone((string) $phone);

        if ($canonical === null) {
            return ['state' => 'invalid', 'total' => 0, 'delivered' => 0, 'cancelled' => 0, 'level' => 'unknown'];
        }

        try {
            $response = FraudCheckSettings::provider() === 'custom'
                ? $this->customRequest($canonical)
                : Http::withHeaders([
                    'Api-Key' => FraudCheckSettings::credential('fraud_steadfast_api_key'),
                    'Secret-Key' => FraudCheckSettings::credential('fraud_steadfast_secret_key'),
                ])
                    ->connectTimeout(3)
                    ->timeout(8)
                    ->withOptions(['allow_redirects' => false])
                    ->get('https://portal.steadfast.com.bd/api/v1/fraud_check/'.$canonical);
        } catch (Throwable) {
            Log::warning('Fraud check failed.');

            return ['state' => 'failed', 'total' => 0, 'delivered' => 0, 'cancelled' => 0, 'level' => 'unknown'];
        }

        if ($response === null || ! $response->successful()) {
            Log::warning('Fraud check failed.', ['status' => $response?->status()]);

            return ['state' => 'failed', 'total' => 0, 'delivered' => 0, 'cancelled' => 0, 'level' => 'unknown'];
        }

        $json = $response->json();

        if (FraudCheckSettings::provider() === 'custom') {
            $total = $this->findNumber($json, FraudCheckSettings::word('fraud_word_total', 'total_parcels'));
            $delivered = $this->findNumber($json, FraudCheckSettings::word('fraud_word_delivered', 'total_delivered'));
            $cancelled = $this->findNumber($json, FraudCheckSettings::word('fraud_word_cancelled', 'total_cancelled'));

            if ($total === null && $delivered === null && $cancelled === null) {
                return ['state' => 'failed', 'total' => 0, 'delivered' => 0, 'cancelled' => 0, 'level' => 'unknown'];
            }
        } else {
            $data = is_array($json['data'] ?? null) ? $json['data'] : (is_array($json) ? $json : []);
            $total = (int) ($data['total_parcels'] ?? 0);
            $delivered = (int) ($data['total_delivered'] ?? $data['total_delivered_parcels'] ?? 0);
            $cancelled = (int) ($data['total_cancelled'] ?? $data['total_cancelled_parcels'] ?? 0);
        }

        $total = (int) $total;
        $delivered = (int) $delivered;
        $cancelled = (int) $cancelled;

        return [
            'state' => 'ready',
            'total' => $total,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'level' => $this->level($delivered, $cancelled, 0, $total),
        ];
    }

    private function customRequest(string $phone): ?Response
    {
        $url = FraudCheckSettings::customUrl();

        if ($url === null) {
            return null;
        }

        $query = [];
        $body = [];
        $parts = parse_url($url);

        if (isset($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        $base = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').(isset($parts['port']) ? ':'.$parts['port'] : '').($parts['path'] ?? '');

        if (FraudCheckSettings::phoneInField()) {
            $target = FraudCheckSettings::customMethod() === 'post' ? 'body' : 'query';

            if ($target === 'body') {
                $body[FraudCheckSettings::phoneField()] = $phone;
            } else {
                $query[FraudCheckSettings::phoneField()] = $phone;
            }
        } else {
            $base = rtrim($base, '/').'/'.$phone;
        }

        $apiKey = FraudCheckSettings::credential('fraud_custom_api_key');
        $headers = [FraudCheckSettings::keyName() => $apiKey];

        if (FraudCheckSettings::keyOnLink()) {
            $query[FraudCheckSettings::keyName()] = $apiKey;
        }

        $extraName = FraudCheckSettings::extraKeyName();
        $extra = FraudCheckSettings::credential('fraud_custom_secret');

        if ($extraName !== '' && filled($extra)) {
            $headers[$extraName] = $extra;
        }

        $final = $base.($query === [] ? '' : '?'.http_build_query($query));
        $pending = Http::withHeaders($headers)
            ->connectTimeout(3)
            ->timeout(8)
            ->withOptions(['allow_redirects' => false]);

        return FraudCheckSettings::customMethod() === 'post'
            ? $pending->asJson()->post($final, $body)
            : $pending->get($final);
    }

    private function findNumber(mixed $node, string $word): ?int
    {
        if ($word === '' || ! is_array($node)) {
            return null;
        }

        if (array_key_exists($word, $node) && is_numeric($node[$word])) {
            return (int) $node[$word];
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $found = $this->findNumber($value, $word);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    public function worse(string $left, string $right): string
    {
        $rank = ['unknown' => 0, 'clear' => 1, 'caution' => 2, 'risk' => 3];

        return ($rank[$left] ?? 0) >= ($rank[$right] ?? 0) ? $left : $right;
    }

    public function level(int $delivered, int $cancelled, int $returned, int $total): string
    {
        $bad = $cancelled + $returned;

        if ($total === 0) {
            return 'unknown';
        }

        if ($bad >= 2 && ($delivered === 0 || ($bad / $total) >= 0.5)) {
            return 'risk';
        }

        if ($total >= 2 && ($bad / $total) >= 0.4) {
            return 'caution';
        }

        if ($delivered > 0 && $bad === 0) {
            return 'clear';
        }

        return $bad > $delivered ? 'caution' : 'clear';
    }
}
