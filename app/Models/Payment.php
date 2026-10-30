<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Exceptions\PaymentException;
use Database\Factories\PaymentFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'payment_number',
    'order_id',
    'customer_id',
    'payment_method',
    'status',
    'amount',
    'currency',
    'transaction_id',
    'account_number',
    'paid_at',
    'recorded_by',
    'notes',
    'metadata',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'BDT',
        'status' => PaymentTransactionStatus::Pending,
        'payment_method' => PaymentMethod::Cod,
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
            'recorded_by' => 'integer',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentTransactionStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (blank($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber();
            }

            if (blank($payment->currency)) {
                $payment->currency = 'BDT';
            }

            if (blank($payment->recorded_by) && auth()->check()) {
                $payment->recorded_by = auth()->id();
            }
        });

        static::saved(function (Payment $payment): void {
            if ($payment->order) {
                $payment->order->synchronizePaymentStatus();
            }
        });
    }

    /**
     * Generate a unique, recognizable payment reference number.
     */
    public static function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (static::where('payment_number', $number)->exists());

        return $number;
    }

    /**
     * Get the order associated with this payment.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the customer profile linked to this payment.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the admin or staff user who recorded/verified the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Mark the payment as completed atomically and update order status.
     *
     * @throws PaymentException
     */
    public function markAsCompleted(?User $actor = null, ?DateTimeInterface $paidAt = null): self
    {
        return DB::transaction(function () use ($actor, $paidAt): self {
            /** @var self $lockedPayment */
            $lockedPayment = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === PaymentTransactionStatus::Completed) {
                throw PaymentException::paymentAlreadyCompleted($lockedPayment->payment_number);
            }

            if ($lockedPayment->status === PaymentTransactionStatus::Refunded) {
                throw PaymentException::paymentAlreadyRefunded($lockedPayment->payment_number);
            }

            $lockedPayment->status = PaymentTransactionStatus::Completed;
            $lockedPayment->paid_at = $paidAt ?? now();
            if ($actor) {
                $lockedPayment->recorded_by = $actor->getKey();
            }
            $lockedPayment->save();

            // Synchronize parent order status under row lock
            $lockedPayment->order->synchronizePaymentStatus();

            $this->setRawAttributes($lockedPayment->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Mark the payment as failed atomically.
     *
     * @throws PaymentException
     */
    public function markAsFailed(?string $reason = null, ?User $actor = null): self
    {
        return DB::transaction(function () use ($reason, $actor): self {
            /** @var self $lockedPayment */
            $lockedPayment = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === PaymentTransactionStatus::Completed) {
                throw PaymentException::paymentAlreadyCompleted($lockedPayment->payment_number);
            }

            $lockedPayment->status = PaymentTransactionStatus::Failed;
            if ($reason) {
                $lockedPayment->notes = trim(($lockedPayment->notes ? $lockedPayment->notes.' | ' : '')."Failed: {$reason}");
            }
            if ($actor) {
                $lockedPayment->recorded_by = $actor->getKey();
            }
            $lockedPayment->save();

            $lockedPayment->order->synchronizePaymentStatus();

            $this->setRawAttributes($lockedPayment->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Refund this completed payment atomically.
     *
     * @throws PaymentException
     */
    public function refund(?string $reason = null, ?User $actor = null): self
    {
        return DB::transaction(function () use ($reason, $actor): self {
            /** @var self $lockedPayment */
            $lockedPayment = static::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== PaymentTransactionStatus::Completed) {
                throw PaymentException::cannotRefundUncompletedPayment(
                    $lockedPayment->payment_number,
                    $lockedPayment->status->value
                );
            }

            $lockedPayment->status = PaymentTransactionStatus::Refunded;
            if ($reason) {
                $lockedPayment->notes = trim(($lockedPayment->notes ? $lockedPayment->notes.' | ' : '')."Refunded: {$reason}");
            }
            if ($actor) {
                $lockedPayment->recorded_by = $actor->getKey();
            }
            $lockedPayment->save();

            $lockedPayment->order->synchronizePaymentStatus();

            $this->setRawAttributes($lockedPayment->getAttributes(), true);

            return $this;
        });
    }

    /**
     * Determine whether this payment can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this->status === PaymentTransactionStatus::Completed;
    }

    /**
     * Check if payment transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === PaymentTransactionStatus::Completed;
    }

    /**
     * Check if payment transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentTransactionStatus::Pending;
    }

    /**
     * Check if payment transaction failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentTransactionStatus::Failed;
    }

    /**
     * Check if payment transaction is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === PaymentTransactionStatus::Refunded;
    }
}
