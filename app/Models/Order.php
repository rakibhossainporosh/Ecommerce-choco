<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
