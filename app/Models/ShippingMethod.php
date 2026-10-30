<?php

namespace App\Models;

use App\Enums\ShippingProvider;
use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'code',
    'provider',
    'charge',
    'free_shipping_threshold',
    'estimated_days_min',
    'estimated_days_max',
    'is_active',
    'description',
])]
class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'provider' => ShippingProvider::InHouse,
        'charge' => 0.00,
        'is_active' => true,
        'estimated_days_min' => 1,
        'estimated_days_max' => 3,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'provider' => ShippingProvider::class,
            'charge' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'estimated_days_min' => 'integer',
            'estimated_days_max' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active shipping methods.
     *
     * @param  Builder<ShippingMethod>  $query
     * @return Builder<ShippingMethod>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the shipments associated with this shipping method.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'shipping_method_id');
    }

    /**
     * Calculate effective shipping cost taking free shipping thresholds into account.
     */
    public function calculateShippingCharge(float $subtotal): float
    {
        if ($this->free_shipping_threshold !== null && $subtotal >= (float) $this->free_shipping_threshold) {
            return 0.00;
        }

        return (float) $this->charge;
    }

    /**
     * Get a human-readable estimate string for delivery window.
     */
    public function getEstimatedDeliveryText(): string
    {
        if ($this->estimated_days_min === $this->estimated_days_max) {
            return "{$this->estimated_days_min} day(s)";
        }

        return "{$this->estimated_days_min}–{$this->estimated_days_max} days";
    }
}
