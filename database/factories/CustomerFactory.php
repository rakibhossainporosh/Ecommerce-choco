<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->name(),
            'phone' => '01'.fake()->unique()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the customer has an associated registered user account.
     */
    public function withUser(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user) {
            $account = $user ?? User::factory()->create();

            return [
                'user_id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
            ];
        });
    }

    /**
     * Indicate that the customer is inactive/blocked.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
