<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('??????-####')),
            'name' => $this->faker->words(3, true),
            'type' => CouponType::Percentage,
            'value' => $this->faker->randomFloat(2, 5, 50),
            'minimum_order_amount' => $this->faker->optional(0.5)->randomFloat(2, 100, 1000),
            'maximum_discount_amount' => $this->faker->optional(0.5)->randomFloat(2, 50, 500),
            'usage_limit' => $this->faker->optional(0.5)->numberBetween(10, 100),
            'usage_limit_per_user' => $this->faker->optional(0.5)->numberBetween(1, 5),
            'used_count' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Fixed,
            'value' => $this->faker->randomFloat(2, 100, 500),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
