<?php

namespace App\Models;

use App\Enums\CouponType;
use Database\Factories\CouponFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'code',
    'name',
    'type',
    'value',
    'minimum_order_amount',
    'maximum_discount_amount',
    'usage_limit',
    'usage_limit_per_user',
    'used_count',
    'starts_at',
    'expires_at',
    'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $casts = [
        'type' => CouponType::class,
        'value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon) {
            $coupon->validateBusinessRules();
        });
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function validateBusinessRules(): void
    {
        $this->code = Str::upper(trim($this->code));

        if ($this->value < 0) {
            throw new DomainException('Coupon value cannot be negative.');
        }

        if ($this->type === CouponType::Percentage && $this->value > 100) {
            throw new DomainException('Percentage coupon value cannot exceed 100%.');
        }

        if ($this->type === CouponType::Fixed && $this->value <= 0) {
            throw new DomainException('Fixed coupon value must be greater than zero.');
        }

        if ($this->minimum_order_amount !== null && $this->minimum_order_amount < 0) {
            throw new DomainException('Minimum order amount cannot be negative.');
        }

        if ($this->maximum_discount_amount !== null && $this->maximum_discount_amount < 0) {
            throw new DomainException('Maximum discount amount cannot be negative.');
        }

        if ($this->usage_limit !== null && $this->usage_limit <= 0) {
            throw new DomainException('Usage limit must be positive.');
        }

        if ($this->usage_limit_per_user !== null && $this->usage_limit_per_user <= 0) {
            throw new DomainException('Usage limit per user must be positive.');
        }

        if ($this->used_count < 0) {
            throw new DomainException('Used count cannot be negative.');
        }

        if ($this->usage_limit !== null && $this->used_count > $this->usage_limit) {
            throw new DomainException('Used count cannot exceed usage limit.');
        }

        if ($this->starts_at !== null && $this->expires_at !== null && $this->starts_at->isAfter($this->expires_at)) {
            throw new DomainException('Start date cannot be after expiry date.');
        }
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->minimum_order_amount !== null && $subtotal < $this->minimum_order_amount) {
            return 0.0;
        }

        $discount = 0.0;

        if ($this->type === CouponType::Percentage) {
            $discount = ($subtotal * $this->value) / 100;
        } elseif ($this->type === CouponType::Fixed) {
            $discount = $this->value;
        }

        if ($this->maximum_discount_amount !== null && $discount > $this->maximum_discount_amount) {
            $discount = $this->maximum_discount_amount;
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return max(0.0, round($discount, 2));
    }

    public function checkEligibility(float $subtotal, ?int $userId = null): void
    {
        if (! $this->is_active) {
            throw new DomainException('Coupon is inactive.');
        }

        $now = now();
        if ($this->starts_at !== null && $now->isBefore($this->starts_at)) {
            throw new DomainException('Coupon is not yet valid.');
        }

        if ($this->expires_at !== null && $now->isAfter($this->expires_at)) {
            throw new DomainException('Coupon has expired.');
        }

        if ($this->minimum_order_amount !== null && $subtotal < $this->minimum_order_amount) {
            throw new DomainException('Order subtotal does not meet the minimum amount for this coupon.');
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            throw new DomainException('Coupon usage limit has been reached.');
        }

        if ($this->usage_limit_per_user !== null && $userId !== null) {
            $userUsage = $this->usages()->where('user_id', $userId)->count();
            if ($userUsage >= $this->usage_limit_per_user) {
                throw new DomainException('You have reached the usage limit for this coupon.');
            }
        }
    }

    public function consumeForOrder(Order $order): CouponUsage
    {
        return DB::transaction(function () use ($order) {
            // Lock coupon for update to prevent concurrent usage limit breaches
            $coupon = self::where('id', $this->id)->lockForUpdate()->firstOrFail();

            $subtotal = (float) $order->subtotal;

            // Re-check eligibility with the fresh locked coupon instance
            $coupon->checkEligibility($subtotal, $order->user_id);

            // Double check order hasn't used a coupon already?
            // In our domain, an order can only have one coupon, but we just insert usage.
            // The unique constraint on coupon_usages might cover it.

            $discountAmount = $coupon->calculateDiscount($subtotal);

            if ($discountAmount <= 0) {
                throw new DomainException('Coupon does not provide any discount for this order.');
            }

            $usage = $coupon->usages()->create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'discount_amount' => $discountAmount,
            ]);

            $coupon->increment('used_count');

            return $usage;
        });
    }
}
