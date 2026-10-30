<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'api_base_url',
        'credentials',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credentials' => 'encrypted:array', // Ensures credentials are encrypted in the DB
    ];

    /**
     * Get the shipments associated with the courier.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(CourierShipment::class, 'courier_id');
    }
}
