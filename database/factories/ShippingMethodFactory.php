<?php

namespace Database\Factories;

use App\Enums\ShippingProvider;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Inside Dhaka Express', 'Inside Dhaka Standard', 'Outside Dhaka Courier', 'Sub-Dhaka Regular']),
            'code' => 'ship_'.fake()->unique()->slug(2),
            'provider' => fake()->randomElement([
                ShippingProvider::Steadfast,
                ShippingProvider::Pathao,
                ShippingProvider::RedX,
                ShippingProvider::InHouse,
            ]),
            'charge' => fake()->randomElement([60.00, 70.00, 80.00, 120.00, 130.00]),
            'free_shipping_threshold' => 2000.00,
            'estimated_days_min' => 1,
            'estimated_days_max' => 3,
            'is_active' => true,
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the method is for Inside Dhaka deliveries.
     */
    public function insideDhaka(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Inside Dhaka Regular',
            'code' => 'inside_dhaka_'.fake()->unique()->numerify('###'),
            'provider' => ShippingProvider::Steadfast,
            'charge' => 70.00,
            'estimated_days_min' => 1,
            'estimated_days_max' => 2,
        ]);
    }

    /**
     * Indicate that the method is for Outside Dhaka deliveries.
     */
    public function outsideDhaka(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Outside Dhaka Regular',
            'code' => 'outside_dhaka_'.fake()->unique()->numerify('###'),
            'provider' => ShippingProvider::Steadfast,
            'charge' => 130.00,
            'estimated_days_min' => 2,
            'estimated_days_max' => 4,
        ]);
    }

    /**
     * Indicate that the shipping method is disabled.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
