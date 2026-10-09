<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id',
    'name',
    'phone',
    'email',
    'is_active',
    'notes',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the registered user account linked to this customer (if any).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all postal/delivery addresses recorded for this customer.
     *
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    /**
     * Get the primary default shipping address.
     *
     * @return HasOne<CustomerAddress, $this>
     */
    public function defaultShippingAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')
            ->where('type', 'shipping')
            ->where('is_default', true);
    }

    /**
     * Get the primary default billing address.
     *
     * @return HasOne<CustomerAddress, $this>
     */
    public function defaultBillingAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')
            ->where('type', 'billing')
            ->where('is_default', true);
    }

    /**
     * Get all orders placed by this customer.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Determine whether the customer record can be safely deleted.
     * To preserve financial records and historical business audits,
     * customers with placed orders may never be deleted.
     */
    public function canBeDeleted(): bool
    {
        return $this->orders()->count() === 0;
    }

    /**
     * Calculate lifetime monetary spend (LTV) excluding cancelled orders.
     */
    public function getTotalSpentAttribute(): float
    {
        return (float) $this->orders()
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->sum('grand_total');
    }

    /**
     * Get total placed order count.
     */
    public function getOrdersCountAttribute(): int
    {
        return $this->orders()->count();
    }

    /**
     * Determine if customer has a linked storefront user account.
     */
    public function getHasUserAccountAttribute(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Scope query to only active customers.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to customers with registered storefront user accounts.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeRegistered(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope query to guest customers without registered user accounts.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeGuest(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }
}
