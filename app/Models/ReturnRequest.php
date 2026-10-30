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

        if ($this->order) {
            if ($this->order->isCancelled()) {
                throw new DomainException('Cannot create a return request for a cancelled order.');
            }

            if ((float) $this->refund_amount > (float) $this->order->grand_total) {
                throw new DomainException('Refund amount cannot exceed the order grand total.');
            }

            $otherRefundsTotal = (float) static::query()
                ->where('order_id', $this->order_id)
                ->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))
                ->whereIn('status', [
                    ReturnRequestStatus::Pending,
                    ReturnRequestStatus::Approved,
                    ReturnRequestStatus::Completed,
                ])
                ->sum('refund_amount');

            if (($otherRefundsTotal + (float) $this->refund_amount) > (float) $this->order->grand_total) {
                throw new DomainException('Total refund amount across return requests cannot exceed the order grand total.');
            }
        }

        if ($this->exists && $this->isDirty('status')) {
            $originalStatus = $this->getOriginal('status');
            $originalStatusEnum = $originalStatus instanceof ReturnRequestStatus
                ? $originalStatus
                : ReturnRequestStatus::tryFrom((string) $originalStatus);

            if ($originalStatusEnum === ReturnRequestStatus::Completed && $this->status !== ReturnRequestStatus::Completed) {
                throw new DomainException('Completed return requests cannot change status.');
            }

            if ($originalStatusEnum === ReturnRequestStatus::Rejected && $this->status !== ReturnRequestStatus::Rejected) {
                throw new DomainException('Rejected return requests cannot change status.');
            }
        }

        if ($this->exists && $this->isDirty('refund_amount')) {
            $originalStatus = $this->getOriginal('status');
            $originalStatusEnum = $originalStatus instanceof ReturnRequestStatus
                ? $originalStatus
                : ReturnRequestStatus::tryFrom((string) $originalStatus);

            if ($originalStatusEnum === ReturnRequestStatus::Completed) {
                throw new DomainException('Refund amount cannot be modified once the return request is completed.');
            }
        }

        if ($this->reason !== null) {
            $this->reason = trim($this->reason);
        }
    }

    public function isPending(): bool
    {
        return $this->status === ReturnRequestStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === ReturnRequestStatus::Approved;
    }

    public function isCompleted(): bool
    {
        return $this->status === ReturnRequestStatus::Completed;
    }

    public function isRejected(): bool
    {
        return $this->status === ReturnRequestStatus::Rejected;
    }
}
