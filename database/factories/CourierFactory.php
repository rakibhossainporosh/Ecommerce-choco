<?php

namespace Database\Factories;

use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Courier>
 */
class CourierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => $this->faker->unique()->slug(),
            'api_base_url' => $this->faker->url(),
            'credentials' => [
                'api_key' => $this->faker->uuid(),
                'secret_key' => $this->faker->password(),
            ],
            'is_active' => true,
        ];
    }
}
