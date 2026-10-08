<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'type' => 'shipping',
            'name' => null,
            'phone' => null,
            'address_line' => fake()->streetAddress(),
            'area' => fake()->citySuffix(),
            'city' => fake()->randomElement(['Dhaka', 'Chittagong', 'Sylhet', 'Rajshahi']),
            'postcode' => fake()->postcode(),
            'country' => 'Bangladesh',
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the address is a billing address.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'billing',
        ]);
    }

    /**
     * Indicate that the address is the default address.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
