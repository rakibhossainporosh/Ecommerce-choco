<?php

namespace App\Models;

use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'customer_id',
    'type',
    'name',
    'phone',
    'address_line',
    'area',
    'city',
    'postcode',
    'country',
    'is_default',
])]
class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'shipping',
        'country' => 'Bangladesh',
        'is_default' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (CustomerAddress $address): void {
            if ($address->is_default) {
                static::where('customer_id', $address->customer_id)
                    ->when($address->id, fn ($query) => $query->where('id', '!=', $address->id))
                    ->where('type', $address->type)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the owning customer.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Atomically promote this address as the default for its type.
     */
    public function setAsDefault(): void
    {
        DB::transaction(function (): void {
            static::where('customer_id', $this->customer_id)
                ->where('id', '!=', $this->id)
                ->where('type', $this->type)
                ->update(['is_default' => false]);

            $this->update(['is_default' => true]);
        });
    }

    /**
     * Format a complete postal address string.
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line,
            $this->area,
            $this->city.($this->postcode ? " - {$this->postcode}" : ''),
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
