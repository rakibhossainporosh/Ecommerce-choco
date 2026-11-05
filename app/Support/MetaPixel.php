<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaPixel
{
    public const SESSION_KEY = 'meta_pixel_events';

    public static function id(): ?string
    {
        $value = Setting::get('meta_pixel_id');

        return self::normalizeId(is_string($value) ? $value : null);
    }

    public static function normalizeId(?string $value): ?string
    {
        $id = preg_replace('/\D+/', '', (string) $value) ?? '';

        return preg_match('/^\d{5,20}$/', $id) === 1 ? $id : null;
    }

    /**
     * @return array{id: ?string, currency: string, events: list<array{name: string, event_id: string, params: array<string, mixed>}>}
     */
    public static function share(): array
    {
        return [
            'id' => self::id(),
            'currency' => (string) config('currency.code', 'BDT'),
            'events' => self::pullEvents(),
        ];
    }

    public static function rememberPurchase(Order $order, ?Request $request = null): void
    {
        $event = self::purchaseEvent($order);
        self::flash($event);
        self::sendPurchase($order, $event, $request ?? request());
    }

    public static function rememberRegistration(int $customerId): void
    {
        self::flash([
            'name' => 'CompleteRegistration',
            'event_id' => 'register-'.$customerId,
            'params' => [
                'status' => true,
            ],
        ]);
    }

    /**
     * @return array{name: string, event_id: string, params: array<string, mixed>}
     */
    public static function purchaseEvent(Order $order): array
    {
        $order->loadMissing('items.productVariant:id,product_id');

        $contents = [];
        $contentIds = [];
        $numItems = 0;

        foreach ($order->items as $item) {
            $id = (string) ($item->productVariant?->product_id ?: $item->product_variant_id ?: $item->sku);
            $quantity = (int) $item->quantity;
            $contents[] = [
                'id' => $id,
                'quantity' => $quantity,
                'item_price' => round((float) $item->unit_price, 2),
            ];
            $contentIds[] = $id;
            $numItems += $quantity;
        }

        return [
            'name' => 'Purchase',
            'event_id' => 'purchase-'.$order->order_number,
            'params' => [
                'content_ids' => array_values(array_unique($contentIds)),
                'content_type' => 'product',
                'contents' => $contents,
                'currency' => (string) ($order->currency ?: config('currency.code', 'BDT')),
                'num_items' => $numItems,
                'value' => round((float) $order->grand_total, 2),
                'order_id' => (string) $order->order_number,
            ],
        ];
    }

    /**
     * @param  array{name: string, event_id: string, params: array<string, mixed>}  $event
     */
    public static function flash(array $event): void
    {
        $events = session()->get(self::SESSION_KEY, []);
        $events = is_array($events) ? $events : [];
        $events[] = $event;
        session()->flash(self::SESSION_KEY, array_values($events));
    }

    /**
     * @return list<array{name: string, event_id: string, params: array<string, mixed>}>
     */
    public static function pullEvents(): array
    {
        $events = session()->pull(self::SESSION_KEY, []);

        if (! is_array($events)) {
            return [];
        }

        return array_values(array_filter($events, fn (mixed $event): bool => is_array($event) && isset($event['name'], $event['event_id'])));
    }

    /**
     * @param  array{name: string, event_id: string, params: array<string, mixed>}  $event
     */
    public static function sendPurchase(Order $order, array $event, Request $request): void
    {
        $pixelId = self::id();
        $token = Setting::get('meta_pixel_access_token');

        if ($pixelId === null || ! is_string($token) || trim($token) === '') {
            return;
        }

        $userData = array_filter([
            'ph' => self::hashedPhone($order->customer_phone),
            'em' => self::hashedEmail($order->customer_email),
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $request->cookie('_fbc'),
        ], fn (mixed $value): bool => is_string($value) && $value !== '');

        try {
            $response = Http::connectTimeout(2)
                ->timeout(4)
                ->acceptJson()
                ->post('https://graph.facebook.com/v21.0/'.$pixelId.'/events', [
                    'data' => [[
                        'event_name' => 'Purchase',
                        'event_time' => now()->timestamp,
                        'event_id' => $event['event_id'],
                        'action_source' => 'website',
                        'event_source_url' => route('checkout.success', ['order_number' => $order->order_number]),
                        'user_data' => $userData,
                        'custom_data' => $event['params'],
                    ]],
                    'access_token' => trim($token),
                ]);

            if ($response->failed()) {
                Log::warning('Meta Conversions API did not accept the purchase event.', [
                    'status' => $response->status(),
                    'order_number' => $order->order_number,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Meta Conversions API purchase request failed.', [
                'order_number' => $order->order_number,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return list<string>|null
     */
    private static function hashedPhone(?string $phone): ?array
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '880'.substr($digits, 1);
        }

        if ($digits === '') {
            return null;
        }

        return [hash('sha256', $digits)];
    }

    /**
     * @return list<string>|null
     */
    private static function hashedEmail(?string $email): ?array
    {
        $normalized = strtolower(trim((string) $email));

        if ($normalized === '') {
            return null;
        }

        return [hash('sha256', $normalized)];
    }
}
