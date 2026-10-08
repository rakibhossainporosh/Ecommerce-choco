<?php

namespace App\Models;

use App\Enums\CourierShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'courier_id',
        'tracking_number',
        'status',
        'shipped_at',
        'delivered_at',
        'response_data',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'courier_id' => 'integer',
        'status' => CourierShipmentStatus::class,
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'response_data' => 'array',
    ];

    /**
     * Get the courier associated with the shipment.
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    /**
     * Get the order associated with the shipment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
