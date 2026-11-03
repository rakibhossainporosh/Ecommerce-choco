<?php

namespace App\Models;

use Database\Factories\ReturnRequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'return_request_id',
    'order_item_id',
    'quantity',
    'refund_amount',
])]
class ReturnRequestItem extends Model
{
    /** @use HasFactory<ReturnRequestItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_request_id' => 'integer',
            'order_item_id' => 'integer',
            'quantity' => 'integer',
            'refund_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ReturnRequest, $this>
     */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
