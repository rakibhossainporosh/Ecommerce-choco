<?php

namespace App\Models;

use App\Enums\AbandonedCartStatus;
use Database\Factories\AbandonedCartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'recovered_order_id',
    'name',
    'phone',
    'email',
    'items',
    'subtotal',
    'token',
    'status',
    'last_activity_at',
    'reminded_at',
    'remind_attempts',
])]
class AbandonedCart extends Model
{
    /** @use HasFactory<AbandonedCartFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'subtotal' => 'decimal:2',
            'status' => AbandonedCartStatus::class,
            'last_activity_at' => 'datetime',
            'reminded_at' => 'datetime',
            'remind_attempts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function recoveredOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'recovered_order_id');
    }
}
