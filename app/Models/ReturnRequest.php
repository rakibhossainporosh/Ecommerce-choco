<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnRequestStatus;
use App\Support\ReturnItemValidator;
use App\Support\ReturnPolicySettings;
use Database\Factories\ReturnRequestFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'rma_number',
    'order_id',
    'user_id',
    'customer_id',
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
        'customer_id' => 'integer',
        'status' => ReturnRequestStatus::class,
        'refund_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReturnRequest $returnRequest): void {
            if (blank($returnRequest->rma_number)) {
                $returnRequest->rma_number = static::generateRmaNumber();
            }
        });

        static::saving(function (ReturnRequest $returnRequest): void {
            $returnRequest->validateBusinessRules();
        });

        static::created(function (ReturnRequest $returnRequest): void {
            $returnRequest->recordStatusHistory(
                from: null,
                to: $returnRequest->status instanceof ReturnRequestStatus
                    ? $returnRequest->status
                    : ReturnRequestStatus::Pending,
                reason: 'Return request created',
            );
        });

        static::updated(function (ReturnRequest $returnRequest): void {
            if (! $returnRequest->wasChanged('status')) {
                return;
            }

            $from = $returnRequest->getOriginal('status');
            $fromEnum = $from instanceof ReturnRequestStatus
                ? $from
                : ReturnRequestStatus::tryFrom((string) $from);

            $returnRequest->recordStatusHistory(
                from: $fromEnum,
                to: $returnRequest->status,
                reason: $returnRequest->admin_note,
            );
        });
    }

    public static function generateRmaNumber(): string
    {
        do {
            $number = 'RMA-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (static::query()->where('rma_number', $number)->exists());

        return $number;
    }

    /**
     * Whether the order already has an in-flight return (pending / approved / received).
     */
    public static function hasOpenReturnForOrder(int $orderId): bool
    {
        return static::query()
            ->where('order_id', $orderId)
            ->whereIn('status', [
                ReturnRequestStatus::Pending,
                ReturnRequestStatus::Approved,
                ReturnRequestStatus::Received,
            ])
            ->exists();
    }

    /**
     * Create a pending return with validated line items inside one transaction.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{order_item_id?: mixed, quantity?: mixed, refund_amount?: mixed}>  $lineItems
     */
    public static function createPendingForOrder(Order $order, array $attributes, array $lineItems = []): self
    {
        return DB::transaction(function () use ($order, $attributes, $lineItems): self {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->with('items')->firstOrFail();

            if (static::hasOpenReturnForOrder((int) $order->id)) {
                throw new DomainException('This order already has an open return request.');
            }

            $providedLines = is_array($lineItems) ? $lineItems : [];
            $normalized = ReturnItemValidator::normalize($order, $providedLines);
            $autoExpanded = false;

            // Empty lines mean "return all remaining units" when the order has catalog lines.
            if ($normalized === []) {
                $remainingLines = ReturnItemValidator::remainingLines($order);

                if ($remainingLines !== []) {
                    $normalized = ReturnItemValidator::normalize($order, $remainingLines);
                    $autoExpanded = true;
                } elseif ($order->items->isNotEmpty()) {
                    throw new DomainException('No returnable items remain on this order.');
                }
                // else: order has no line items — money-only RMA ticket (legacy / admin).
            }

            if ($normalized !== []) {
                $fullRefund = ReturnItemValidator::refundTotal($order, $normalized);

                if ($autoExpanded) {
                    $requested = array_key_exists('refund_amount', $attributes)
                        ? (float) $attributes['refund_amount']
                        : $fullRefund;

                    if ($requested < 0 || ($requested - $fullRefund) > 0.009) {
                        throw new DomainException('Refund amount cannot exceed the returnable order value.');
                    }

                    // Full goods return; money may be partial (goodwill), default is full value.
                    $attributes['refund_amount'] = round($requested > 0 ? min($requested, $fullRefund) : $fullRefund, 2);
                } else {
                    $attributes['refund_amount'] = $fullRefund;
                }
            } else {
                $attributes['refund_amount'] = (float) ($attributes['refund_amount'] ?? $order->grand_total);
            }

            $attributes['order_id'] = $order->id;
            $attributes['customer_id'] = $attributes['customer_id'] ?? $order->customer_id;
            $attributes['user_id'] = $attributes['user_id'] ?? $order->user_id;
            $attributes['status'] = ReturnRequestStatus::Pending;

            unset($attributes['line_items']);

            /** @var self $returnRequest */
            $returnRequest = static::query()->create($attributes);

            if ($normalized !== []) {
                ReturnItemValidator::syncItems($returnRequest, $normalized);
            }

            return $returnRequest->load('items');
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<ReturnRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
    }

    /**
     * @return HasMany<ReturnRequestStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ReturnRequestStatusHistory::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
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

            if (! $this->exists) {
                if (! $this->order->isDelivered() && $this->order->status !== OrderStatus::Returned) {
                    throw new DomainException('Return requests are only allowed for delivered orders.');
                }

                if (! $this->order->isWithinReturnWindow()) {
                    throw new DomainException(
                        'The return window of '.ReturnPolicySettings::windowDays().' day(s) after delivery has expired.'
                    );
                }
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
                    ReturnRequestStatus::Received,
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

            if (in_array($originalStatusEnum, [
                ReturnRequestStatus::Completed,
                ReturnRequestStatus::Rejected,
                ReturnRequestStatus::Cancelled,
            ], true) && $this->status !== $originalStatusEnum) {
                throw new DomainException('Terminal return requests cannot change status.');
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

    public function isCancelled(): bool
    {
        return $this->status === ReturnRequestStatus::Cancelled;
    }

    public function isReceived(): bool
    {
        return $this->status === ReturnRequestStatus::Received;
    }

    public function approve(?User $actor = null, ?string $note = null): self
    {
        if ($this->status !== ReturnRequestStatus::Pending) {
            throw new DomainException('Only pending return requests can be approved.');
        }

        return DB::transaction(function () use ($note): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== ReturnRequestStatus::Pending) {
                throw new DomainException('Only pending return requests can be approved.');
            }

            if ($note) {
                $locked->admin_note = trim(($locked->admin_note ? $locked->admin_note.' | ' : '').$note);
            }

            $locked->status = ReturnRequestStatus::Approved;
            $locked->save();

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    public function reject(string $reason, ?User $actor = null): self
    {
        $trimmed = trim($reason);

        if ($trimmed === '') {
            throw new DomainException('A rejection reason is required.');
        }

        if (! in_array($this->status, [
            ReturnRequestStatus::Pending,
            ReturnRequestStatus::Approved,
            ReturnRequestStatus::Received,
        ], true)) {
            throw new DomainException('This return request cannot be rejected.');
        }

        return DB::transaction(function () use ($trimmed): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [
                ReturnRequestStatus::Pending,
                ReturnRequestStatus::Approved,
                ReturnRequestStatus::Received,
            ], true)) {
                throw new DomainException('This return request cannot be rejected.');
            }

            $locked->status = ReturnRequestStatus::Rejected;
            $locked->admin_note = $trimmed;
            $locked->save();

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Mark approved goods as received at warehouse / inspection.
     */
    public function markReceived(?User $actor = null, ?string $note = null): self
    {
        if ($this->status !== ReturnRequestStatus::Approved) {
            throw new DomainException('Only approved return requests can be marked as received.');
        }

        return DB::transaction(function () use ($note): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== ReturnRequestStatus::Approved) {
                throw new DomainException('Only approved return requests can be marked as received.');
            }

            if ($note) {
                $locked->admin_note = trim(($locked->admin_note ? $locked->admin_note.' | ' : '').$note);
            }

            $locked->status = ReturnRequestStatus::Received;
            $locked->save();

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Customer may cancel only while the request is still pending review.
     */
    public function cancelByCustomer(): self
    {
        if ($this->status !== ReturnRequestStatus::Pending) {
            throw new DomainException('Only pending return requests can be cancelled.');
        }

        return DB::transaction(function (): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== ReturnRequestStatus::Pending) {
                throw new DomainException('Only pending return requests can be cancelled.');
            }

            $locked->status = ReturnRequestStatus::Cancelled;
            $locked->admin_note = trim(($locked->admin_note ? $locked->admin_note.' | ' : '').'Cancelled by customer');
            $locked->save();

            $this->setRawAttributes($locked->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Whether this return covers every ordered unit (full-order return).
     */
    public function coversEntireOrder(?Order $order = null): bool
    {
        $order ??= $this->order;

        if (! $order) {
            return false;
        }

        $returnItems = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        if ($returnItems->isEmpty()) {
            return true;
        }

        $orderItems = $order->relationLoaded('items') ? $order->items : $order->items()->get();

        foreach ($orderItems as $orderItem) {
            $returnedQty = (int) $returnItems
                ->where('order_item_id', $orderItem->id)
                ->sum('quantity');

            if ($returnedQty < (int) $orderItem->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Complete the return: restock, refund payments, mark order Returned/Refunded when applicable.
     *
     * Inventory / status mutations commit first. Gateway-backed refunds run after commit so a
     * rolled-back shop state cannot follow a successful provider refund.
     */
    public function complete(?User $actor = null, ?string $note = null): self
    {
        if ($this->status !== ReturnRequestStatus::Received) {
            throw new DomainException('Only received return requests can be completed.');
        }

        $refundAmount = 0.0;
        $orderId = null;
        $rmaLabel = null;

        $completed = DB::transaction(function () use ($actor, $note, &$refundAmount, &$orderId, &$rmaLabel): self {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $locked->load(['items.orderItem', 'order.items']);

            if ($locked->status !== ReturnRequestStatus::Received) {
                throw new DomainException('Only received return requests can be completed.');
            }

            if ($note) {
                $locked->admin_note = trim(($locked->admin_note ? $locked->admin_note.' | ' : '').$note);
            }

            /** @var Order|null $order */
            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->first();

            if ($order) {
                $order->load('items');

                // Legacy empty-line RMAs with catalog lines: materialize remaining units first.
                if ($locked->items->isEmpty() && $order->items->isNotEmpty()) {
                    $normalized = ReturnItemValidator::normalize(
                        $order,
                        ReturnItemValidator::remainingLines($order, $locked->id),
                        $locked->id,
                    );

                    if ($normalized === []) {
                        throw new DomainException('No returnable items remain to complete this return.');
                    }

                    ReturnItemValidator::syncItems($locked, $normalized);
                    $locked->load(['items.orderItem']);
                }

                if ($locked->items->isNotEmpty()) {
                    $order->restoreStockFromReturnItems($locked, $actor);

                    if ($locked->coversEntireOrder($order) && $order->canTransitionTo(OrderStatus::Returned)) {
                        $order->markAsReturned($actor, restoreStock: false);
                    }
                } elseif ($order->canTransitionTo(OrderStatus::Returned)) {
                    // Money-only / no catalog lines: full order return without line restock ledger.
                    $order->markAsReturned($actor, restoreStock: true);
                }

                $refundAmount = (float) $locked->refund_amount;
                $orderId = (int) $order->id;
                $rmaLabel = (string) ($locked->rma_number ?? $locked->id);
            }

            $locked->status = ReturnRequestStatus::Completed;
            $locked->save();

            $this->setRawAttributes($locked->getAttributes(), true);
            $this->setRelation('items', $locked->items);

            return $this;
        });

        if ($orderId && $refundAmount > 0.009) {
            /** @var Order $order */
            $order = Order::query()->findOrFail($orderId);

            $order->refundCompletedPayments(
                reason: __('Return :rma', ['rma' => $rmaLabel]),
                actor: $actor,
                upToAmount: $refundAmount,
            );

            $order->refresh();

            if (
                $order->canTransitionTo(OrderStatus::Refunded)
                && $order->payment_status === PaymentStatus::Refunded
            ) {
                $order->markAsRefunded($actor);
            }
        }

        return $completed->refresh()->load('items');
    }

    protected function recordStatusHistory(?ReturnRequestStatus $from, ReturnRequestStatus $to, ?string $reason = null): void
    {
        $actor = auth()->user();
        $changedBy = $actor instanceof User ? $actor->getKey() : null;

        $this->statusHistories()->create([
            'changed_by' => $changedBy,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'reason' => $reason ? trim($reason) : null,
            'created_at' => now(),
        ]);
    }
}
