<?php

namespace App\Models;

use App\Casts\PaymentMethodCast;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\InventoryException;
use App\Exceptions\PaymentException;
use App\Exceptions\ShippingException;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentManager;
use App\Support\ReturnPolicySettings;
use Database\Factories\OrderFactory;
use DateTimeInterface;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'order_number',
    'customer_id',
    'user_id',
    'customer_name',
    'customer_phone',
    'customer_email',
    'customer_note',
    'status',
    'payment_status',
    'payment_method',
    'currency',
    'subtotal',
    'discount_amount',
    'shipping_amount',
    'grand_total',
    'shipping_address_line',
    'shipping_area',
    'shipping_city',
    'shipping_postcode',
    'shipping_country',
    'billing_same_as_shipping',
    'billing_address_line',
    'billing_area',
    'billing_city',
    'billing_postcode',
    'billing_country',
    'placed_at',
    'cancelled_at',
    'cancelled_by',
    'cancellation_reason',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'BDT',
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
        'payment_method' => PaymentMethod::Cod,
        'discount_amount' => 0.00,
        'shipping_amount' => 0.00,
        'billing_same_as_shipping' => true,
        'shipping_country' => 'Bangladesh',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'user_id' => 'integer',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethodCast::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'billing_same_as_shipping' => 'boolean',
            'placed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancelled_by' => 'integer',
        ];
    }

    /**
     * Get the customer CRM profile associated with this order.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the registered customer user who placed the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the order items associated with this order.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Alias for items().
     *
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->items();
    }

    /**
     * Get all payment transactions recorded for this order.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Get the total amount paid through completed payments.
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', PaymentTransactionStatus::Completed->value)
            ->sum('amount');
    }

    /**
     * Get the remaining balance due for this order.
     */
    public function getDueAmountAttribute(): float
    {
        return max(0.00, round((float) $this->grand_total - $this->total_paid, 2));
    }

    /**
     * Get the shipments associated with this order.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'order_id')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Get the courier shipments associated with this order.
     *
     * @return HasMany<CourierShipment, $this>
     */
    public function courierShipments(): HasMany
    {
        return $this->hasMany(CourierShipment::class, 'order_id')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Get the latest shipment for this order.
     *
     * @return HasOne<Shipment, $this>
     */
    public function latestShipment(): HasOne
    {
        return $this->hasOne(Shipment::class, 'order_id')
            ->latestOfMany('created_at');
    }

    /**
     * Create a shipment for this order with address snapshot.
     *
     * @throws ShippingException
     */
    public function createShipment(
        ShippingProvider|string $provider = ShippingProvider::InHouse,
        ?ShippingMethod $shippingMethod = null,
        ?string $trackingCode = null,
        ?float $weightKg = null,
        ?float $shippingCharge = null,
        ?string $notes = null,
        ?User $actor = null,
    ): Shipment {
        if ($this->isCancelled()) {
            throw ShippingException::cannotShipCancelledOrder($this->order_number);
        }

        $prov = $provider instanceof ShippingProvider ? $provider : (ShippingProvider::tryFrom($provider) ?? ShippingProvider::InHouse);

        return $this->shipments()->create([
            'customer_id' => $this->customer_id,
            'shipping_method_id' => $shippingMethod?->id,
            'provider' => $prov,
            'status' => ShipmentStatus::Pending,
            'tracking_code' => $trackingCode,
            'shipping_charge' => $shippingCharge ?? (float) $this->shipping_amount,
            'weight_kg' => $weightKg,
            'recipient_name' => $this->customer_name,
            'recipient_phone' => $this->customer_phone,
            'shipping_address_line' => $this->shipping_address_line,
            'shipping_area' => $this->shipping_area,
            'shipping_city' => $this->shipping_city ?? 'Dhaka',
            'shipping_postcode' => $this->shipping_postcode,
            'shipping_country' => $this->shipping_country ?? 'Bangladesh',
            'dispatched_by' => $actor?->id ?? auth()->id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Accept a known enum or any gateway slug that fits the payment column.
     *
     * @throws PaymentException
     */
    private function paymentMethodSlug(PaymentMethod|string $method): string
    {
        $slug = strtolower(trim($method instanceof PaymentMethod ? $method->value : $method));

        if (preg_match('/^[a-z0-9_]{2,30}$/', $slug) !== 1) {
            throw PaymentException::unknownMethod($slug);
        }

        return $slug;
    }

    /**
     * Record a payment transaction for this order atomically with row locking.
     *
     * @throws PaymentException
     */
    public function recordPayment(
        float $amount,
        PaymentMethod|string $method = PaymentMethod::Cod,
        PaymentTransactionStatus|string $status = PaymentTransactionStatus::Completed,
        ?string $transactionId = null,
        ?string $accountNumber = null,
        ?string $notes = null,
        ?User $recordedBy = null,
        ?DateTimeInterface $paidAt = null,
    ): Payment {
        if ($amount <= 0) {
            throw PaymentException::invalidAmount($amount);
        }

        return DB::transaction(function () use (
            $amount,
            $method,
            $status,
            $transactionId,
            $accountNumber,
            $notes,
            $recordedBy,
            $paidAt
        ): Payment {
            /** @var self $lockedOrder */
            $lockedOrder = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status === OrderStatus::Cancelled) {
                throw PaymentException::cannotRecordForCancelledOrder($lockedOrder->order_number);
            }

            $txStatus = $status instanceof PaymentTransactionStatus ? $status : PaymentTransactionStatus::from($status);
            $payMethod = $this->paymentMethodSlug($method);

            /** @var Payment $payment */
            $payment = $lockedOrder->payments()->create([
                'customer_id' => $lockedOrder->customer_id,
                'payment_method' => $payMethod,
                'status' => $txStatus,
                'amount' => $amount,
                'currency' => $lockedOrder->currency ?? 'BDT',
                'transaction_id' => $transactionId,
                'account_number' => $accountNumber,
                'paid_at' => $txStatus === PaymentTransactionStatus::Completed ? ($paidAt ?? now()) : null,
                'recorded_by' => $recordedBy?->getKey() ?? auth()->id(),
                'notes' => $notes,
            ]);

            // Synchronize overall order payment_status
            $lockedOrder->synchronizePaymentStatus();

            $this->setRawAttributes($lockedOrder->getAttributes(), true);

            return $payment;
        });
    }

    /**
     * Synchronize the order's payment_status based on completed and refunded payments.
     */
    public function synchronizePaymentStatus(): void
    {
        DB::transaction(function (): void {
            /** @var self $lockedOrder */
            $lockedOrder = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $completedPaid = (float) $lockedOrder->payments()
                ->where('status', PaymentTransactionStatus::Completed->value)
                ->sum('amount');

            $refundedTotal = (float) $lockedOrder->payments()
                ->where('status', PaymentTransactionStatus::Refunded->value)
                ->sum('amount');

            $grandTotal = (float) $lockedOrder->grand_total;

            // Ledger-style partial refunds leave capture rows Completed while writing
            // Refunded ledger rows. Treat economically-full coverage as Refunded.
            $captureEconomicallyRefunded = $refundedTotal > 0
                && ($completedPaid <= 0.009 || ($refundedTotal + 0.009) >= $completedPaid);

            if ($captureEconomicallyRefunded) {
                $lockedOrder->payment_status = PaymentStatus::Refunded;
            } elseif ($refundedTotal > 0 && $completedPaid > 0) {
                $lockedOrder->payment_status = PaymentStatus::PartiallyRefunded;
            } elseif ($completedPaid >= $grandTotal && $grandTotal > 0) {
                $lockedOrder->payment_status = PaymentStatus::Paid;
            } elseif ($completedPaid > 0) {
                $lockedOrder->payment_status = PaymentStatus::Partial;
            } else {
                $hasPending = $lockedOrder->payments()
                    ->where('status', PaymentTransactionStatus::Pending->value)
                    ->exists();

                $lockedOrder->payment_status = $hasPending
                    ? PaymentStatus::Pending
                    : PaymentStatus::Unpaid;
            }

            $lockedOrder->save();
            $this->setRawAttributes($lockedOrder->getAttributes(), true);
        });
    }

    /**
     * Get the user who cancelled the order, if applicable.
     *
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get all status history records for this order.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Get the latest status history record for this order.
     *
     * @return HasOne<OrderStatusHistory, $this>
     */
    public function latestStatusHistory(): HasOne
    {
        return $this->hasOne(OrderStatusHistory::class, 'order_id')
            ->latestOfMany('created_at');
    }

    /**
     * Check if the order can transition to the given status.
     */
    public function canTransitionTo(OrderStatus $target): bool
    {
        return $this->status->canTransitionTo($target);
    }

    /**
     * Check if the order can be cancelled from its current status.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Transition the order to a new status atomically with row locking.
     *
     * @throws InvalidOrderTransitionException
     */
    public function transitionTo(OrderStatus $targetStatus): self
    {
        return $this->executeTransition($targetStatus);
    }

    /**
     * Internal atomic transition runner with pessimistic row locking.
     *
     * @throws InvalidOrderTransitionException
     */
    /**
     * @param  array{restore_stock?: bool}  $options
     */
    protected function executeTransition(
        OrderStatus $targetStatus,
        ?string $cancellationReason = null,
        ?User $cancelledBy = null,
        array $options = [],
    ): self {
        if (! $this->exists) {
            throw InvalidOrderTransitionException::unsavedOrder();
        }

        return DB::transaction(function () use ($targetStatus, $cancellationReason, $cancelledBy, $options): self {
            /** @var self $lockedOrder */
            $lockedOrder = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->canTransitionTo($targetStatus)) {
                throw InvalidOrderTransitionException::cannotTransition($lockedOrder->status, $targetStatus);
            }

            $fromStatus = $lockedOrder->status;
            $actor = $cancelledBy ?? auth()->user();

            // Deduct stock and record sale movements when transitioning to Confirmed
            if ($targetStatus === OrderStatus::Confirmed) {
                $orderItems = $lockedOrder->items()->get();

                if ($orderItems->isNotEmpty()) {
                    /** @var array<int, int> $requiredQuantities */
                    $requiredQuantities = [];
                    /** @var array<int, string> $variantSkus */
                    $variantSkus = [];

                    foreach ($orderItems as $item) {
                        $variantId = $item->product_variant_id;
                        $requiredQuantities[$variantId] = ($requiredQuantities[$variantId] ?? 0) + $item->quantity;
                        if (! isset($variantSkus[$variantId])) {
                            $variantSkus[$variantId] = $item->sku ?? "Variant #{$variantId}";
                        }
                    }

                    $variantIds = array_keys($requiredQuantities);
                    sort($variantIds, SORT_NUMERIC);

                    /** @var Collection<int, Inventory> $lockedInventories */
                    $lockedInventories = Inventory::query()
                        ->whereIn('product_variant_id', $variantIds)
                        ->orderBy('product_variant_id', 'asc')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('product_variant_id');

                    // Check all variants have initialized inventory records
                    foreach ($variantIds as $variantId) {
                        if (! $lockedInventories->has($variantId)) {
                            $sku = $variantSkus[$variantId] ?? "Variant #{$variantId}";
                            throw InventoryException::notInitializedForOrder($sku);
                        }
                    }

                    // Validate stock availability for all variants before mutating
                    foreach ($variantIds as $variantId) {
                        /** @var Inventory $inventory */
                        $inventory = $lockedInventories->get($variantId);
                        $required = $requiredQuantities[$variantId];

                        if ($inventory->quantity < $required) {
                            $sku = $variantSkus[$variantId] ?? "Variant #{$variantId}";
                            throw InventoryException::insufficientStockForOrder($sku, $required, $inventory->quantity);
                        }
                    }

                    // Deduct stock and create sale movements
                    foreach ($orderItems as $item) {
                        /** @var Inventory $inventory */
                        $inventory = $lockedInventories->get($item->product_variant_id);
                        $quantityBefore = $inventory->quantity;
                        $quantityAfter = $quantityBefore - $item->quantity;

                        $inventory->quantity = $quantityAfter;
                        $inventory->save();

                        $inventory->movements()->create([
                            'product_variant_id' => $inventory->product_variant_id,
                            'type' => InventoryMovementType::Sale,
                            'quantity' => $item->quantity,
                            'quantity_before' => $quantityBefore,
                            'quantity_after' => $quantityAfter,
                            'reference_type' => Order::class,
                            'reference_id' => $lockedOrder->id,
                            'reason' => 'Sale',
                            'note' => "Order #{$lockedOrder->order_number}",
                            'created_by' => $actor?->getKey(),
                        ]);
                    }
                }
            }

            // Restock inventory when cancelling after stock was reserved, or when marking returned
            $shouldRestoreStock = (bool) ($options['restore_stock'] ?? true);

            if (
                $shouldRestoreStock
                && in_array($targetStatus, [OrderStatus::Cancelled, OrderStatus::Returned], true)
            ) {
                $lockedOrder->restoreReservedStock($actor, $targetStatus === OrderStatus::Cancelled
                    ? 'Order cancelled — stock restored'
                    : 'Order returned — stock restored');
            }

            // Cancellation requires a non-empty reason and records metadata
            if ($targetStatus === OrderStatus::Cancelled) {
                $trimmedReason = trim((string) $cancellationReason);

                if ($trimmedReason === '') {
                    throw InvalidOrderTransitionException::missingCancellationReason($lockedOrder->status);
                }

                if (mb_strlen($trimmedReason) > 255) {
                    throw InvalidOrderTransitionException::cancellationReasonTooLong(255, $lockedOrder->status);
                }

                $lockedOrder->status = OrderStatus::Cancelled;
                $lockedOrder->cancelled_at = now();
                $lockedOrder->cancelled_by = $actor?->getKey();
                $lockedOrder->cancellation_reason = $trimmedReason;
            } else {
                $lockedOrder->status = $targetStatus;
            }

            $lockedOrder->save();

            // Create immutable status history entry within the same atomic transaction
            $lockedOrder->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $targetStatus,
                'changed_by' => $actor?->getKey(),
                'reason' => $targetStatus === OrderStatus::Cancelled ? $trimmedReason : null,
            ]);

            // Synchronize in-memory model state and clear relation cache
            $this->setRawAttributes($lockedOrder->getAttributes(), true);
            $this->unsetRelation('cancelledBy');
            $this->unsetRelation('statusHistories');
            $this->unsetRelation('latestStatusHistory');

            return $this;
        });
    }

    /**
     * Confirm the order.
     *
     * @throws InvalidOrderTransitionException
     */
    public function confirm(): self
    {
        return $this->transitionTo(OrderStatus::Confirmed);
    }

    /**
     * Start processing the order.
     *
     * @throws InvalidOrderTransitionException
     */
    public function startProcessing(): self
    {
        return $this->transitionTo(OrderStatus::Processing);
    }

    /**
     * Mark the order as shipped.
     *
     * @throws InvalidOrderTransitionException
     */
    public function ship(): self
    {
        return $this->transitionTo(OrderStatus::Shipped);
    }

    /**
     * Ship the order and ensure a consignment exists/moves into transit.
     *
     * Prefer this from admin actions so "Shipped" never drifts from logistics.
     *
     * @throws InvalidOrderTransitionException
     * @throws ShippingException
     */
    public function shipWithConsignment(?User $actor = null, ?string $trackingCode = null): self
    {
        return DB::transaction(function () use ($actor, $trackingCode): self {
            /** @var self $lockedOrder */
            $lockedOrder = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $openStatuses = [
                ShipmentStatus::Pending,
                ShipmentStatus::Packed,
                ShipmentStatus::InTransit,
                ShipmentStatus::OutForDelivery,
                ShipmentStatus::FailedDelivery,
            ];

            /** @var Shipment|null $shipment */
            $shipment = $lockedOrder->shipments()
                ->whereIn('status', $openStatuses)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $shipment) {
                $shipment = $lockedOrder->createShipment(
                    provider: ShippingProvider::InHouse,
                    trackingCode: $trackingCode,
                    actor: $actor,
                );
            }

            if ($shipment->status === ShipmentStatus::Pending && $shipment->status->canTransitionTo(ShipmentStatus::Packed)) {
                $shipment->markAsPacked($actor);
                $shipment->refresh();
            }

            if ($shipment->status->canTransitionTo(ShipmentStatus::InTransit)) {
                $shipment->markAsShipped($trackingCode, $actor);
            } elseif ($lockedOrder->fresh()?->canTransitionTo(OrderStatus::Shipped)) {
                $lockedOrder->ship();
            }

            $this->refresh();

            return $this;
        });
    }

    /**
     * Mark the order as delivered.
     *
     * @throws InvalidOrderTransitionException
     */
    public function deliver(): self
    {
        return $this->transitionTo(OrderStatus::Delivered);
    }

    /**
     * Mark a delivered order as returned (optionally restocks reserved inventory).
     *
     * @throws InvalidOrderTransitionException
     */
    public function markAsReturned(?User $user = null, bool $restoreStock = true): self
    {
        return $this->executeTransition(OrderStatus::Returned, null, $user, [
            'restore_stock' => $restoreStock,
        ]);
    }

    /**
     * Whether the order is still inside the configured return window after delivery.
     */
    public function isWithinReturnWindow(?DateTimeInterface $asOf = null): bool
    {
        $deliveredAt = $this->deliveredAt();

        if (! $deliveredAt) {
            return $this->isDelivered();
        }

        $deadline = $deliveredAt->copy()->addDays(ReturnPolicySettings::windowDays())->endOfDay();
        $compareAt = $asOf ? Carbon::parse($asOf) : now();

        return $compareAt->lte($deadline);
    }

    /**
     * Best-effort delivery timestamp from status history, then updated_at.
     */
    public function deliveredAt(): ?Carbon
    {
        $history = $this->statusHistories()
            ->where('to_status', OrderStatus::Delivered->value)
            ->latest('id')
            ->first();

        if ($history?->created_at) {
            return $history->created_at->copy();
        }

        return $this->isDelivered() ? $this->updated_at?->copy() : null;
    }

    /**
     * Restock quantities for a line-item return request (idempotent per return request).
     */
    public function restoreStockFromReturnItems(ReturnRequest $returnRequest, ?User $actor = null): void
    {
        $alreadyRestored = InventoryMovement::query()
            ->where('reference_type', ReturnRequest::class)
            ->where('reference_id', $returnRequest->getKey())
            ->where('type', InventoryMovementType::Return)
            ->exists();

        if ($alreadyRestored) {
            return;
        }

        $returnItems = $returnRequest->relationLoaded('items')
            ? $returnRequest->items
            : $returnRequest->items()->with('orderItem')->get();

        if ($returnItems->isEmpty()) {
            return;
        }

        /** @var array<int, int> $restoreQuantities */
        $restoreQuantities = [];

        foreach ($returnItems as $returnItem) {
            $orderItem = $returnItem->orderItem;

            if (! $orderItem?->product_variant_id) {
                continue;
            }

            $variantId = (int) $orderItem->product_variant_id;
            $restoreQuantities[$variantId] = ($restoreQuantities[$variantId] ?? 0) + (int) $returnItem->quantity;
        }

        if ($restoreQuantities === []) {
            return;
        }

        $variantIds = array_keys($restoreQuantities);
        sort($variantIds, SORT_NUMERIC);

        /** @var Collection<int, Inventory> $lockedInventories */
        $lockedInventories = Inventory::query()
            ->whereIn('product_variant_id', $variantIds)
            ->orderBy('product_variant_id', 'asc')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_variant_id');

        foreach ($restoreQuantities as $variantId => $quantity) {
            /** @var Inventory|null $inventory */
            $inventory = $lockedInventories->get($variantId);

            if (! $inventory || $quantity < 1) {
                continue;
            }

            $quantityBefore = $inventory->quantity;
            $quantityAfter = $quantityBefore + $quantity;

            $inventory->quantity = $quantityAfter;
            $inventory->save();

            $inventory->movements()->create([
                'product_variant_id' => $inventory->product_variant_id,
                'type' => InventoryMovementType::Return,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference_type' => ReturnRequest::class,
                'reference_id' => $returnRequest->getKey(),
                'reason' => 'Return request restock',
                'note' => "Order #{$this->order_number} / Return #{$returnRequest->id}",
                'created_by' => $actor?->getKey(),
            ]);
        }
    }

    /**
     * Mark a returned order as refunded after payment reversal.
     *
     * @throws InvalidOrderTransitionException
     */
    public function markAsRefunded(?User $user = null): self
    {
        return $this->executeTransition(OrderStatus::Refunded, null, $user);
    }

    /**
     * Refund completed payments on this order (full payment rows).
     *
     * @return Collection<int, Payment>
     */
    public function refundCompletedPayments(?string $reason = null, ?User $actor = null, ?float $upToAmount = null): Collection
    {
        $refunded = new Collection;

        $remaining = $upToAmount;
        $payments = $this->payments()
            ->where('status', PaymentTransactionStatus::Completed)
            ->orderBy('id')
            ->get();

        foreach ($payments as $payment) {
            if ($remaining !== null && $remaining <= 0) {
                break;
            }

            if ($remaining !== null && (float) $payment->amount > $remaining) {
                // Full-row refund only; oversized payments are covered by a ledger entry below.
                continue;
            }

            $payment->refund($reason, $actor);
            $refunded->push($payment->fresh());

            if ($remaining !== null) {
                $remaining -= (float) $payment->amount;
            }
        }

        if ($remaining !== null && $remaining > 0.009) {
            $refunded->push($this->recordPartialRefundLedger(
                amount: round($remaining, 2),
                reason: $reason,
                actor: $actor,
            ));
        }

        $this->refresh();

        return $refunded;
    }

    /**
     * Record a partial refund amount without mutating an oversized completed payment row.
     * Online gateways are asked to refund the partial amount against the source capture first.
     */
    protected function recordPartialRefundLedger(float $amount, ?string $reason = null, ?User $actor = null): Payment
    {
        if ($amount <= 0) {
            throw new DomainException('Partial refund ledger amount must be greater than zero.');
        }

        /** @var Payment|null $sourcePayment */
        $sourcePayment = $this->payments()
            ->where('status', PaymentTransactionStatus::Completed)
            ->orderByDesc('amount')
            ->orderBy('id')
            ->first();

        if ($sourcePayment) {
            $method = $sourcePayment->payment_method;
            $slug = $method instanceof PaymentMethod ? $method->value : (is_string($method) ? $method : '');
            $gateway = $slug !== '' ? app(PaymentManager::class)->find($slug) : null;

            if ($gateway) {
                $metadata = is_array($sourcePayment->metadata) ? $sourcePayment->metadata : [];
                $metadata['refund_amount_override'] = round($amount, 2);
                $sourcePayment->metadata = $metadata;

                try {
                    $gateway->refundOnline($sourcePayment, trim((string) $reason));
                } catch (PaymentGatewayException $exception) {
                    throw new PaymentException($exception->getMessage());
                }
            }
        }

        /** @var Payment $payment */
        $payment = $this->payments()->create([
            'customer_id' => $this->customer_id,
            'payment_method' => $sourcePayment?->payment_method ?? $this->payment_method,
            'status' => PaymentTransactionStatus::Refunded,
            'amount' => $amount,
            'currency' => 'BDT',
            'paid_at' => now(),
            'recorded_by' => $actor?->getKey() ?? auth()->id(),
            'notes' => trim(($reason ? $reason.' | ' : '').'Partial refund ledger entry'),
            'metadata' => [
                'type' => 'partial_refund_ledger',
                'order_id' => $this->getKey(),
                'source_payment_id' => $sourcePayment?->getKey(),
            ],
        ]);

        $this->synchronizePaymentStatus();

        return $payment->fresh();
    }

    /**
     * Cancel the order with a mandatory reason and optional cancelling user.
     *
     * @throws InvalidOrderTransitionException
     */
    public function cancel(string $reason, ?User $user = null): self
    {
        $trimmedReason = trim($reason);

        if ($trimmedReason === '') {
            throw InvalidOrderTransitionException::missingCancellationReason();
        }

        return $this->executeTransition(OrderStatus::Cancelled, $trimmedReason, $user);
    }

    /**
     * Restore inventory previously deducted on confirm (idempotent per order).
     */
    protected function restoreReservedStock(?User $actor, string $reason): void
    {
        $alreadyRestored = InventoryMovement::query()
            ->where('reference_type', self::class)
            ->where('reference_id', $this->getKey())
            ->where('type', InventoryMovementType::Return)
            ->exists();

        if ($alreadyRestored) {
            return;
        }

        $hadSale = InventoryMovement::query()
            ->where('reference_type', self::class)
            ->where('reference_id', $this->getKey())
            ->where('type', InventoryMovementType::Sale)
            ->exists();

        if (! $hadSale) {
            return;
        }

        $orderItems = $this->items()->get();

        if ($orderItems->isEmpty()) {
            return;
        }

        /** @var array<int, int> $restoreQuantities */
        $restoreQuantities = [];

        foreach ($orderItems as $item) {
            $variantId = $item->product_variant_id;
            $restoreQuantities[$variantId] = ($restoreQuantities[$variantId] ?? 0) + $item->quantity;
        }

        $variantIds = array_keys($restoreQuantities);
        sort($variantIds, SORT_NUMERIC);

        /** @var Collection<int, Inventory> $lockedInventories */
        $lockedInventories = Inventory::query()
            ->whereIn('product_variant_id', $variantIds)
            ->orderBy('product_variant_id', 'asc')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_variant_id');

        foreach ($orderItems as $item) {
            /** @var Inventory|null $inventory */
            $inventory = $lockedInventories->get($item->product_variant_id);

            if (! $inventory) {
                continue;
            }

            $quantityBefore = $inventory->quantity;
            $quantityAfter = $quantityBefore + $item->quantity;

            $inventory->quantity = $quantityAfter;
            $inventory->save();

            $inventory->movements()->create([
                'product_variant_id' => $inventory->product_variant_id,
                'type' => InventoryMovementType::Return,
                'quantity' => $item->quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference_type' => self::class,
                'reference_id' => $this->getKey(),
                'reason' => $reason,
                'note' => "Order #{$this->order_number}",
                'created_by' => $actor?->getKey(),
            ]);
        }
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::Confirmed;
    }

    public function isProcessing(): bool
    {
        return $this->status === OrderStatus::Processing;
    }

    public function isShipped(): bool
    {
        return $this->status === OrderStatus::Shipped;
    }

    public function isDelivered(): bool
    {
        return $this->status === OrderStatus::Delivered;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    public function couponUsage(): HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }
}
