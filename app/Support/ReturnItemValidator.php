<?php

namespace App\Support;

use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use DomainException;
use Illuminate\Support\Collection;

class ReturnItemValidator
{
    /**
     * Normalize and validate return line items against remaining returnable quantities.
     *
     * @param  array<int, array{order_item_id?: mixed, quantity?: mixed, refund_amount?: mixed}>  $lines
     * @return list<array{order_item_id: int, quantity: int, refund_amount: float}>
     */
    public static function normalize(Order $order, array $lines, ?int $excludeReturnRequestId = null): array
    {
        $order->loadMissing('items');

        /** @var array<int, int> $alreadyReturned */
        $alreadyReturned = ReturnRequestItem::query()
            ->whereHas('returnRequest', function ($query) use ($order, $excludeReturnRequestId): void {
                $query->where('order_id', $order->id)
                    ->whereIn('status', [
                        ReturnRequestStatus::Pending,
                        ReturnRequestStatus::Approved,
                        ReturnRequestStatus::Received,
                        ReturnRequestStatus::Completed,
                    ])
                    ->when($excludeReturnRequestId, fn ($q) => $q->where('id', '!=', $excludeReturnRequestId));
            })
            ->selectRaw('order_item_id, SUM(quantity) as qty')
            ->groupBy('order_item_id')
            ->pluck('qty', 'order_item_id')
            ->map(fn ($qty): int => (int) $qty)
            ->all();

        $normalized = [];
        $seen = [];

        foreach ($lines as $line) {
            $orderItemId = (int) ($line['order_item_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($orderItemId < 1 || $quantity < 1) {
                continue;
            }

            if (isset($seen[$orderItemId])) {
                throw new DomainException('Duplicate order items are not allowed on a single return request.');
            }

            $seen[$orderItemId] = true;

            /** @var OrderItem|null $orderItem */
            $orderItem = $order->items->firstWhere('id', $orderItemId);

            if (! $orderItem) {
                throw new DomainException('One or more return items do not belong to this order.');
            }

            $remaining = (int) $orderItem->quantity - (int) ($alreadyReturned[$orderItemId] ?? 0);

            if ($quantity > $remaining) {
                throw new DomainException(
                    "Return quantity for \"{$orderItem->product_name}\" exceeds remaining returnable units ({$remaining})."
                );
            }

            $unitRefund = (int) $orderItem->quantity > 0
                ? ((float) $orderItem->line_total / (int) $orderItem->quantity)
                : 0.0;

            $providedRefund = array_key_exists('refund_amount', $line) && $line['refund_amount'] !== null && $line['refund_amount'] !== ''
                ? (float) $line['refund_amount']
                : round($unitRefund * $quantity, 2);

            if ($providedRefund < 0) {
                throw new DomainException('Line refund amount cannot be negative.');
            }

            $maxLineRefund = round($unitRefund * $quantity, 2);

            if ($providedRefund - $maxLineRefund > 0.009) {
                throw new DomainException('Line refund amount cannot exceed the item line value for the returned quantity.');
            }

            $normalized[] = [
                'order_item_id' => $orderItemId,
                'quantity' => $quantity,
                'refund_amount' => round($providedRefund, 2),
            ];
        }

        return $normalized;
    }

    /**
     * Whether the normalized lines cover every ordered unit that is still returnable.
     *
     * @param  list<array{order_item_id: int, quantity: int, refund_amount: float}>  $normalized
     */
    public static function coversEntireRemainingOrder(Order $order, array $normalized): bool
    {
        $order->loadMissing('items');

        $byItem = collect($normalized)->keyBy('order_item_id');

        foreach ($order->items as $orderItem) {
            $already = (int) ReturnRequestItem::query()
                ->where('order_item_id', $orderItem->id)
                ->whereHas('returnRequest', function ($query) use ($order): void {
                    $query->where('order_id', $order->id)
                        ->whereIn('status', [
                            ReturnRequestStatus::Pending,
                            ReturnRequestStatus::Approved,
                            ReturnRequestStatus::Received,
                            ReturnRequestStatus::Completed,
                        ]);
                })
                ->sum('quantity');

            $remaining = (int) $orderItem->quantity - $already;
            $returning = (int) ($byItem->get($orderItem->id)['quantity'] ?? 0);

            if ($remaining > 0 && $returning < $remaining) {
                return false;
            }
        }

        return $order->items->isNotEmpty();
    }

    /**
     * Product subtotal refund + shipping when the return clears the remaining order.
     *
     * @param  list<array{order_item_id: int, quantity: int, refund_amount: float}>  $normalized
     */
    public static function refundTotal(Order $order, array $normalized, bool $includeShippingWhenFull = true): float
    {
        $productRefund = round(collect($normalized)->sum('refund_amount'), 2);

        if ($normalized === []) {
            return round((float) $order->grand_total, 2);
        }

        if ($includeShippingWhenFull && static::coversEntireRemainingOrder($order, $normalized)) {
            return round($productRefund + (float) $order->shipping_amount, 2);
        }

        return $productRefund;
    }

    /**
     * Persist validated line items onto a return request.
     *
     * @param  list<array{order_item_id: int, quantity: int, refund_amount: float}>  $normalized
     */
    public static function syncItems(ReturnRequest $returnRequest, array $normalized): void
    {
        $returnRequest->items()->delete();

        foreach ($normalized as $line) {
            $returnRequest->items()->create($line);
        }
    }

    /**
     * @return Collection<int, int>
     */
    public static function remainingQuantities(Order $order, ?int $excludeReturnRequestId = null): Collection
    {
        $order->loadMissing('items');

        $alreadyReturned = ReturnRequestItem::query()
            ->whereHas('returnRequest', function ($query) use ($order, $excludeReturnRequestId): void {
                $query->where('order_id', $order->id)
                    ->whereIn('status', [
                        ReturnRequestStatus::Pending,
                        ReturnRequestStatus::Approved,
                        ReturnRequestStatus::Received,
                        ReturnRequestStatus::Completed,
                    ])
                    ->when($excludeReturnRequestId, fn ($q) => $q->where('id', '!=', $excludeReturnRequestId));
            })
            ->selectRaw('order_item_id, SUM(quantity) as qty')
            ->groupBy('order_item_id')
            ->pluck('qty', 'order_item_id');

        return $order->items->mapWithKeys(function (OrderItem $item) use ($alreadyReturned): array {
            $remaining = (int) $item->quantity - (int) ($alreadyReturned[$item->id] ?? 0);

            return [$item->id => max(0, $remaining)];
        });
    }

    /**
     * Build line payloads for every still-returnable unit on the order.
     *
     * @return list<array{order_item_id: int, quantity: int}>
     */
    public static function remainingLines(Order $order, ?int $excludeReturnRequestId = null): array
    {
        $lines = [];

        foreach (static::remainingQuantities($order, $excludeReturnRequestId) as $orderItemId => $quantity) {
            if ((int) $quantity < 1) {
                continue;
            }

            $lines[] = [
                'order_item_id' => (int) $orderItemId,
                'quantity' => (int) $quantity,
            ];
        }

        return $lines;
    }
}
