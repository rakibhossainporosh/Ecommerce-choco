<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uniqueId = fake()->unique()->numberBetween(1000, 999999);
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name).' Unit '.$uniqueId,
            'code' => Str::lower(Str::slug($name)).'-'.$uniqueId,
            'description' => fake()->paragraph(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the unit is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the unit is soft-deleted.
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
