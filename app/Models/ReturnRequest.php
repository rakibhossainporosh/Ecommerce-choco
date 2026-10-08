<?php

namespace App\Models;

use App\Enums\ReturnRequestStatus;
use Database\Factories\ReturnRequestFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'user_id',
    'status',
    'reason',
    'admin_note',
    'refund_amount',
])]
class ReturnRequest extends Model
{
    /** @use HasFactory<ReturnRequestFactory> */
    use HasFactory;

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'status' => ReturnRequestStatus::class,
        'refund_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (ReturnRequest $returnRequest) {
            $returnRequest->validateBusinessRules();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validateBusinessRules(): void
    {
        if ($this->refund_amount < 0) {
            throw new DomainException('Refund amount cannot be negative.');
        }

        if ($this->order && (float) $this->refund_amount > (float) $this->order->grand_total) {
            throw new DomainException('Refund amount cannot exceed the order grand total.');
        }

        if ($this->reason !== null) {
            $this->reason = trim($this->reason);
        }
    }
}
