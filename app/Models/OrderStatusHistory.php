<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'order_id',
    'changed_by',
    'from_status',
    'to_status',
    'reason',
    'created_at',
])]
class OrderStatusHistory extends Model
{
    /** @use HasFactory<OrderStatusHistoryFactory> */
    use HasFactory;

    /**
     * The name of the "updated at" column.
     * Order status history records are strictly immutable and do not have an updated_at timestamp.
     *
     * @var string|null
     */
    public const UPDATED_AT = null;

    /**
     * The model's attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'changed_by' => 'integer',
            'from_status' => OrderStatus::class,
            'to_status' => OrderStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot model events to enforce strict immutability.
     */
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('OrderStatusHistory records are immutable and cannot be updated.');
        });

        static::deleting(function (): never {
            throw new LogicException('OrderStatusHistory records are immutable and cannot be deleted.');
        });
    }

    /**
     * Disallow update operations.
     */
    public function update(array $attributes = [], array $options = []): never
    {
        throw new LogicException('OrderStatusHistory records are immutable and cannot be updated.');
    }

    /**
     * Disallow delete operations.
     */
    public function delete(): never
    {
        throw new LogicException('OrderStatusHistory records are immutable and cannot be deleted.');
    }

    /**
     * Get the order that owns this status history entry.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the user who executed the status transition.
     *
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
