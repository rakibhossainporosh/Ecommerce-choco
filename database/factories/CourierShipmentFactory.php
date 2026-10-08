<?php

namespace Database\Factories;

use App\Enums\CourierShipmentStatus;
use App\Models\Courier;
use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourierShipment>
 */
class CourierShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'courier_id' => Courier::factory(),
            'tracking_number' => $this->faker->bothify('TRK-####-????'),
            'status' => CourierShipmentStatus::Pending,
            'shipped_at' => null,
            'delivered_at' => null,
            'response_data' => null,
        ];
    }
}
