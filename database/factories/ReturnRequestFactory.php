<?php

namespace Database\Factories;

use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnRequest>
 */
class ReturnRequestFactory extends Factory
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
            'user_id' => User::factory(),
            'status' => ReturnRequestStatus::Pending,
            'reason' => $this->faker->sentence(),
            'admin_note' => null,
            'refund_amount' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
