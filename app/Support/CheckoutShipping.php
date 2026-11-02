<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\ShippingMethod;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CheckoutShipping
{
    /**
     * @return list<array{id: int, name: string, code: string, charge: float, free_shipping_threshold: ?float, estimate: string, zone: string}>
     */
    public static function options(): array
    {
        return self::active()
            ->map(fn (ShippingMethod $method): array => [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'charge' => (float) $method->charge,
                'free_shipping_threshold' => $method->free_shipping_threshold !== null
                    ? (float) $method->free_shipping_threshold
                    : null,
                'estimate' => $method->getEstimatedDeliveryText(),
                'zone' => self::zone($method->code),
            ])
            ->values()
            ->all();
    }

    public static function quote(string $city, ?int $methodId, float $subtotal): float
    {
        $matches = self::forCity($city);

        if ($matches->isEmpty()) {
            return self::flatRate($city);
        }

        $method = $methodId ? $matches->firstWhere('id', $methodId) : null;

        if (! $method instanceof ShippingMethod) {
            throw ValidationException::withMessages([
                'shipping_method_id' => __('storefront.checkout.shipping_unavailable'),
            ]);
        }

        return $method->calculateShippingCharge($subtotal);
    }

    /**
     * @return Collection<int, ShippingMethod>
     */
    private static function active(): Collection
    {
        return ShippingMethod::query()->active()->orderBy('charge')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, ShippingMethod>
     */
    private static function forCity(string $city): Collection
    {
        $zone = strcasecmp(trim($city), 'Dhaka') === 0 ? 'inside' : 'outside';

        $matches = self::active()->filter(
            fn (ShippingMethod $method): bool => in_array(self::zone($method->code), [$zone, 'any'], true),
        );

        return $matches->isNotEmpty() ? $matches->values() : self::active();
    }

    private static function zone(string $code): string
    {
        if (str_starts_with($code, 'outside_dhaka')) {
            return 'outside';
        }

        if (str_starts_with($code, 'inside_dhaka')) {
            return 'inside';
        }

        return 'any';
    }

    private static function flatRate(string $city): float
    {
        $inside = (float) Setting::get('shipping_inside_dhaka', 60);
        $outside = (float) Setting::get('shipping_outside_dhaka', 120);

        return strcasecmp(trim($city), 'Dhaka') === 0 ? $inside : $outside;
    }
}
