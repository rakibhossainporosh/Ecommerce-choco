<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uniqueId = fake()->unique()->numberBetween(1000, 999999);
        $name = fake()->unique()->words(3, true);

        return [
            'brand_id' => null,
            'name' => ucwords($name).' '.$uniqueId,
            'slug' => Str::lower(Str::slug($name)).'-'.$uniqueId,
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'meta_title' => ucwords($name),
            'meta_description' => fake()->sentence(),
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product is soft-deleted.
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
