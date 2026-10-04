<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidOrderTransitionException;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'order_number',
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
            'user_id' => 'integer',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
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
     * Get the user who cancelled the order, if applicable.
     *
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
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
    protected function executeTransition(
        OrderStatus $targetStatus,
        ?string $cancellationReason = null,
        ?User $cancelledBy = null,
    ): self {
        if (! $this->exists) {
            throw InvalidOrderTransitionException::unsavedOrder();
        }

        return DB::transaction(function () use ($targetStatus, $cancellationReason, $cancelledBy): self {
            /** @var self $lockedOrder */
            $lockedOrder = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->canTransitionTo($targetStatus)) {
                throw InvalidOrderTransitionException::cannotTransition($lockedOrder->status, $targetStatus);
            }

            // Cancellation requires a non-empty reason and records metadata
            if ($targetStatus === OrderStatus::Cancelled) {
                $trimmedReason = trim((string) $cancellationReason);

                if ($trimmedReason === '') {
                    throw InvalidOrderTransitionException::missingCancellationReason($lockedOrder->status);
                }

                $lockedOrder->status = OrderStatus::Cancelled;
                $lockedOrder->cancelled_at = now();
                $lockedOrder->cancelled_by = $cancelledBy?->getKey();
                $lockedOrder->cancellation_reason = $trimmedReason;
            } else {
                $lockedOrder->status = $targetStatus;
            }

            $lockedOrder->save();

            // Synchronize in-memory model state and clear relation cache
            $this->setRawAttributes($lockedOrder->getAttributes(), true);
            $this->unsetRelation('cancelledBy');

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
     * Mark the order as delivered.
     *
     * @throws InvalidOrderTransitionException
     */
    public function deliver(): self
    {
        return $this->transitionTo(OrderStatus::Delivered);
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
}
