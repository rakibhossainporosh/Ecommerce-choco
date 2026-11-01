<?php

namespace App\Services;

use App\Enums\AbandonedCartStatus;
use App\Mail\AbandonedCartMail;
use App\Models\AbandonedCart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\AbandonedCartSettings;
use App\Support\OrderMailSettings;
use App\Support\OrderSmsSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class AbandonedCartService
{
    public function __construct(private SmsService $sms) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function remember(?Customer $customer, array $input): void
    {
        $phone = $this->phone($input['phone'] ?? null) ?? $this->phone($customer?->phone);
        $email = $this->email($input['email'] ?? null) ?? $this->email($customer?->email);
        $name = $this->shortText($input['name'] ?? null) ?? $this->shortText($customer?->name);

        if ($customer === null && $phone === null) {
            return;
        }

        $lines = $this->lines($input['items'] ?? []);
        $cart = $this->openCart($customer, $phone);

        if ($lines === []) {
            $cart?->delete();

            return;
        }

        $subtotal = array_sum(array_column($lines, 'line_total'));

        if ($cart === null) {
            AbandonedCart::query()->create([
                'customer_id' => $customer?->id,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'items' => $lines,
                'subtotal' => $subtotal,
                'token' => Str::random(40),
                'status' => AbandonedCartStatus::Open,
                'last_activity_at' => now(),
                'remind_attempts' => 0,
            ]);

            return;
        }

        $cart->fill([
            'customer_id' => $customer?->id ?? $cart->customer_id,
            'name' => $name ?? $cart->name,
            'phone' => $phone ?? $cart->phone,
            'email' => $email ?? $cart->email,
            'items' => $lines,
            'subtotal' => $subtotal,
            'status' => AbandonedCartStatus::Open,
            'last_activity_at' => now(),
            'reminded_at' => null,
            'remind_attempts' => 0,
        ]);
        $cart->save();
    }

    public function markRecovered(Order $order): void
    {
        $phone = $this->phone($order->customer_phone);

        AbandonedCart::query()
            ->whereIn('status', [AbandonedCartStatus::Open, AbandonedCartStatus::Reminded])
            ->where(function ($query) use ($order, $phone): void {
                $hasMatch = false;

                if ($order->customer_id) {
                    $query->where('customer_id', $order->customer_id);
                    $hasMatch = true;
                }

                if ($phone !== null) {
                    $hasMatch ? $query->orWhere('phone', $phone) : $query->where('phone', $phone);
                    $hasMatch = true;
                }

                if (! $hasMatch) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->update([
                'status' => AbandonedCartStatus::Recovered->value,
                'recovered_order_id' => $order->id,
            ]);
    }

    public function remindDue(): int
    {
        if (! AbandonedCartSettings::enabled()) {
            return 0;
        }

        $due = AbandonedCart::query()
            ->where('status', AbandonedCartStatus::Open)
            ->where('remind_attempts', '<', 3)
            ->where('last_activity_at', '<=', now()->subMinutes(AbandonedCartSettings::delayMinutes()))
            ->orderBy('last_activity_at')
            ->limit(50)
            ->get();

        $sent = 0;

        foreach ($due as $cart) {
            if ($this->alreadyOrdered($cart)) {
                $cart->status = AbandonedCartStatus::Recovered;
                $cart->save();

                continue;
            }

            $emailed = $this->sendEmail($cart);
            $texted = $this->sendSms($cart);

            if ($emailed || $texted) {
                $cart->status = AbandonedCartStatus::Reminded;
                $cart->reminded_at = now();
                $cart->save();
                $sent++;

                continue;
            }

            $cart->remind_attempts++;
            $cart->save();
        }

        return $sent;
    }

    private function alreadyOrdered(AbandonedCart $cart): bool
    {
        return Order::query()
            ->where('created_at', '>=', $cart->created_at)
            ->where(function ($query) use ($cart): void {
                $hasMatch = false;

                if ($cart->customer_id) {
                    $query->where('customer_id', $cart->customer_id);
                    $hasMatch = true;
                }

                if (filled($cart->phone)) {
                    $phones = [$cart->phone, '88'.$cart->phone, '+88'.$cart->phone];
                    $hasMatch ? $query->orWhereIn('customer_phone', $phones) : $query->whereIn('customer_phone', $phones);
                    $hasMatch = true;
                }

                if (! $hasMatch) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->exists();
    }

    private function sendEmail(AbandonedCart $cart): bool
    {
        if (! AbandonedCartSettings::emailEnabled() || ! OrderMailSettings::enabled() || ! filled($cart->email)) {
            return false;
        }

        $config = OrderMailSettings::mailerConfig();

        if ($config === null) {
            return false;
        }

        try {
            config(['mail.mailers.store' => $config]);
            Mail::purge('store');
            Mail::mailer('store')->to($cart->email)->send(new AbandonedCartMail(
                shopName: OrderMailSettings::fromName(),
                fromAddress: (string) OrderMailSettings::fromAddress(),
                fromName: OrderMailSettings::fromName(),
                subjectLine: AbandonedCartSettings::render(AbandonedCartSettings::emailSubject(), $cart),
                intro: AbandonedCartSettings::render(AbandonedCartSettings::emailBody(), $cart),
                items: is_array($cart->items) ? $cart->items : [],
                total: number_format((float) $cart->subtotal, 0),
                recoverUrl: route('cart.recover', ['token' => $cart->token]),
            ));

            return true;
        } catch (Throwable $exception) {
            Log::error('Abandoned cart email failed: '.$exception->getMessage());

            return false;
        }
    }

    private function sendSms(AbandonedCart $cart): bool
    {
        if (! AbandonedCartSettings::smsEnabled() || ! OrderSmsSettings::enabled() || ! OrderSmsSettings::gatewayReady() || ! filled($cart->phone)) {
            return false;
        }

        $message = AbandonedCartSettings::render(AbandonedCartSettings::smsBody(), $cart);

        if ($message === '') {
            return false;
        }

        try {
            return $this->sms->send($cart->phone, $message);
        } catch (Throwable $exception) {
            Log::error('Abandoned cart SMS failed: '.$exception->getMessage());

            return false;
        }
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array{product_id: int, variant_id: int, name: string, quantity: int, unit_price: float, line_total: float}>
     */
    private function lines(array $items): array
    {
        $lines = [];

        foreach (array_slice($items, 0, 30) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $variant = ProductVariant::query()->with('product')->find($item['variant_id'] ?? null);

            if ($variant === null || $variant->product === null || (int) $variant->product_id !== (int) ($item['id'] ?? 0) || ! $variant->is_active) {
                continue;
            }

            $quantity = max(1, min((int) ($item['quantity'] ?? 1), 20));
            $price = round((float) $variant->selling_price, 2);
            $variantName = trim((string) $variant->name);
            $name = $variant->product->name.($variantName !== '' ? ' - '.$variantName : '');

            $lines[] = [
                'product_id' => (int) $variant->product_id,
                'variant_id' => (int) $variant->id,
                'name' => $name,
                'quantity' => $quantity,
                'unit_price' => $price,
                'line_total' => round($price * $quantity, 2),
            ];
        }

        return $lines;
    }

    private function openCart(?Customer $customer, ?string $phone): ?AbandonedCart
    {
        return AbandonedCart::query()
            ->whereIn('status', [AbandonedCartStatus::Open, AbandonedCartStatus::Reminded])
            ->where(function ($query) use ($customer, $phone): void {
                $hasMatch = false;

                if ($customer !== null) {
                    $query->where('customer_id', $customer->id);
                    $hasMatch = true;
                }

                if ($phone !== null) {
                    $hasMatch ? $query->orWhere('phone', $phone) : $query->where('phone', $phone);
                    $hasMatch = true;
                }

                if (! $hasMatch) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->latest('last_activity_at')
            ->first();
    }

    private function phone(mixed $phone): ?string
    {
        return is_string($phone) ? OrderSmsSettings::canonicalPhone($phone) : null;
    }

    private function email(mixed $email): ?string
    {
        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return mb_substr(strtolower(trim($email)), 0, 255);
    }

    private function shortText(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, 120);
    }
}
