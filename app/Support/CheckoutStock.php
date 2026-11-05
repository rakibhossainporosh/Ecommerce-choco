<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Inventory;
use App\Models\OrderItem;
use App\Models\ProductVariant;

class CheckoutStock
{
    /**
     * Available units after subtracting pending (not-yet-confirmed) order holds.
     */
    public static function availableForVariant(int $variantId, ?int $ignoreOrderId = null): ?int
    {
        /** @var Inventory|null $inventory */
        $inventory = Inventory::query()
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            return null;
        }

        $pending = OrderItem::query()
            ->where('product_variant_id', $variantId)
            ->whereHas('order', function ($query) use ($ignoreOrderId) {
                $query->where('status', OrderStatus::Pending);
                if ($ignoreOrderId) {
                    $query->where('id', '!=', $ignoreOrderId);
                }
            })
            ->sum('quantity');

        return max(0, (int) $inventory->quantity - (int) $pending);
    }

    public static function assertAvailable(ProductVariant $variant, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        $available = self::availableForVariant((int) $variant->id);

        if ($available === null) {
            return;
        }

        if ($available < $quantity) {
            throw new \RuntimeException((string) $available);
        }
    }
}
