<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;

class VerifiedPurchase
{
    /**
     * Whether the customer has a fulfilled (non-cancelled) order containing the product.
     */
    public static function customerBoughtProduct(?Customer $customer, Product $product): bool
    {
        if (! $customer) {
            return false;
        }

        return Order::query()
            ->where(function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);

                if (filled($customer->phone)) {
                    $query->orWhere('customer_phone', $customer->phone);
                }
            })
            ->whereIn('status', [
                OrderStatus::Confirmed,
                OrderStatus::Processing,
                OrderStatus::Shipped,
                OrderStatus::Delivered,
            ])
            ->whereHas('items.productVariant', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->exists();
    }
}
