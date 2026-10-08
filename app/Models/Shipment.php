<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Exceptions\ShippingException;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'shipment_number',
    'order_id',
    'customer_id',
    'shipping_method_id',
    'provider',
    'status',
    'tracking_code',
    'shipping_charge',
    'weight_kg',
    'recipient_name',
    'recipient_phone',
    'shipping_address_line',
    'shipping_area',
    'shipping_city',
    'shipping_postcode',
    'shipping_country',
    'packed_at',
    'shipped_at',
    'delivered_at',
    'returned_at',
    'cancelled_at',
    'dispatched_by',
    'notes',
    'metadata',
])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'provider' => ShippingProvider::InHouse,
        'status' => ShipmentStatus::Pending,
        'shipping_charge' => 0.00,
        'shipping_city' => 'Dhaka',
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
            'order_id' => 'integer',
            'customer_id' => 'integer',
            'shipping_method_id' => 'integer',
            'dispatched_by' => 'integer',
            'provider' => ShippingProvider::class,
            'status' => ShipmentStatus::class,
            'shipping_charge' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'packed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'returned_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment): void {
            if (blank($shipment->shipment_number)) {
                $shipment->shipment_number = static::generateShipmentNumber();
            }

            if (blank($shipment->dispatched_by) && auth()->check()) {
                $shipment->dispatched_by = auth()->id();
            }
        });
    }

    /**
     * Generate a unique shipment reference number.
     */
    public static function generateShipmentNumber(): string
    {
        do {
            $number = 'SHP-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (static::where('shipment_number', $number)->exists());

        return $number;
    }

    /**
     * Get the order associated with this shipment.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the customer associated with this shipment.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the shipping method configured for this consignment.
     *
     * @return BelongsTo<ShippingMethod, $this>
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    /**
     * Get the admin/staff member who dispatched or managed this shipment.
     *
     * @return BelongsTo<User, $this>
     */
    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    /**
     * Mark the shipment as packed and ready to ship.
     *
     * @throws ShippingException
     */
    public function markAsPacked(?User $actor = null): self
    {
        return DB::transaction(function () use ($actor): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(ShipmentStatus::Packed)) {
                throw ShippingException::invalidStatusTransition($locked->status, ShipmentStatus::Packed);
            }

            $locked->status = ShipmentStatus::Packed;
            $locked->packed_at = now();
            if ($actor) {
                $locked->dispatched_by = $actor->getKey();
            }
            $locked->save();

            // Progress order from Confirmed to Processing if applicable
            $order = $locked->order;
            if ($order && $order->status === OrderStatus::Confirmed) {
                $order->startProcessing();
            }

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Dispatch the shipment into transit.
     *
     * @throws ShippingException
     */
    public function markAsShipped(?string $trackingCode = null, ?User $actor = null): self
    {
        return DB::transaction(function () use ($trackingCode, $actor): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(ShipmentStatus::InTransit)) {
                throw ShippingException::invalidStatusTransition($locked->status, ShipmentStatus::InTransit);
            }

            $locked->status = ShipmentStatus::InTransit;
            $locked->shipped_at = now();
            if (filled($trackingCode)) {
                $locked->tracking_code = trim($trackingCode);
            }
            if ($actor) {
                $locked->dispatched_by = $actor->getKey();
            }
            $locked->save();

            // Progress order to Shipped if order can transition
            $order = $locked->order;
            if ($order) {
                if ($order->status === OrderStatus::Confirmed) {
                    $order->startProcessing();
                }
                if ($order->canTransitionTo(OrderStatus::Shipped)) {
                    $order->ship();
                }
            }

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Mark the shipment as out for delivery.
     *
     * @throws ShippingException
     */
    public function markAsOutForDelivery(?User $actor = null): self
    {
        return DB::transaction(function () use ($actor): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(ShipmentStatus::OutForDelivery)) {
                throw ShippingException::invalidStatusTransition($locked->status, ShipmentStatus::OutForDelivery);
            }

            $locked->status = ShipmentStatus::OutForDelivery;
            if ($actor) {
                $locked->dispatched_by = $actor->getKey();
            }
            $locked->save();

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Mark the shipment as successfully delivered.
     *
     * @throws ShippingException
     */
    public function markAsDelivered(?User $actor = null): self
    {
        return DB::transaction(function () use ($actor): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(ShipmentStatus::Delivered)) {
                throw ShippingException::invalidStatusTransition($locked->status, ShipmentStatus::Delivered);
            }

            $locked->status = ShipmentStatus::Delivered;
            $locked->delivered_at = now();
            if ($actor) {
                $locked->dispatched_by = $actor->getKey();
            }
            $locked->save();

            // Transition parent order to Delivered if allowed
            $order = $locked->order;
            if ($order && $order->canTransitionTo(OrderStatus::Delivered)) {
                $order->deliver();
            }

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Record a delivery attempt failure.
     *
     * @throws ShippingException
     */
    public function markAsFailedDelivery(?string $reason = null, ?User $actor = null): self
    {
        return DB::transaction(function () use ($reason, $actor): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(ShipmentStatus::FailedDelivery)) {
                throw ShippingException::invalidStatusTransition($locked->status, ShipmentStatus::FailedDelivery);
            }

            $locked->status = ShipmentStatus::FailedDelivery;
            if ($reason) {
                $locked->notes = trim(($locked->notes ? $locked->notes.' | ' : '')."Attempt Failed: {$reason}");
            }
            if ($actor) {
                $locked->dispatched_by = $actor->getKey();
            }
            $locked->save();

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Mark the shipment as returned to merchant warehouse.
     *
     * @throws ShippingException
     */
    public function markAsReturned(?string $reason = null, ?User $actor = null): self
    {
        return DB::transaction(function () use ($reason, $actor): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(ShipmentStatus::Returned)) {
                throw ShippingException::invalidStatusTransition($locked->status, ShipmentStatus::Returned);
            }

            $locked->status = ShipmentStatus::Returned;
            $locked->returned_at = now();
            if ($reason) {
                $locked->notes = trim(($locked->notes ? $locked->notes.' | ' : '')."Returned: {$reason}");
            }
            if ($actor) {
                $locked->dispatched_by = $actor->getKey();
            }
            $locked->save();

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Cancel the shipment prior to final delivery.
     *
     * @throws ShippingException
     */
    public function cancel(?string $reason = null, ?User $actor = null): self
    {
        return DB::transaction(function () use ($reason, $actor): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(ShipmentStatus::Cancelled)) {
                throw ShippingException::invalidStatusTransition($locked->status, ShipmentStatus::Cancelled);
            }

            $locked->status = ShipmentStatus::Cancelled;
            $locked->cancelled_at = now();
            if ($reason) {
                $locked->notes = trim(($locked->notes ? $locked->notes.' | ' : '')."Cancelled: {$reason}");
            }
            if ($actor) {
                $locked->dispatched_by = $actor->getKey();
            }
            $locked->save();

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Check if shipment can be packed.
     */
    public function canBePacked(): bool
    {
        return $this->status->canTransitionTo(ShipmentStatus::Packed);
    }

    /**
     * Check if shipment can be shipped.
     */
    public function canBeShipped(): bool
    {
        return $this->status->canTransitionTo(ShipmentStatus::InTransit);
    }

    /**
     * Check if shipment can be marked as delivered.
     */
    public function canBeDelivered(): bool
    {
        return $this->status->canTransitionTo(ShipmentStatus::Delivered);
    }

    /**
     * Check if shipment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canTransitionTo(ShipmentStatus::Cancelled);
    }

    /**
     * Get tracking URL if supported by provider.
     */
    public function getTrackingUrl(): ?string
    {
        return $this->provider->trackingUrl($this->tracking_code);
    }
}
