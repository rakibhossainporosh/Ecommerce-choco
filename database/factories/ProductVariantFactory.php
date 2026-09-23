<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uniqueId = fake()->unique()->numberBetween(10000, 999999);
        $costPrice = fake()->randomFloat(2, 50, 500);
        $sellingPrice = $costPrice + fake()->randomFloat(2, 20, 200);
        $compareAtPrice = $sellingPrice + fake()->randomFloat(2, 10, 100);

        return [
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            'name' => fake()->optional(0.7)->word().' '.$uniqueId,
            'sku' => 'SKU-'.strtoupper(fake()->unique()->lexify('????')).'-'.$uniqueId,
            'barcode' => fake()->optional(0.5)->numerify('8809#########'),
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'compare_at_price' => $compareAtPrice,
            'unit_quantity' => fake()->randomElement(['1.000', '250.000', '500.000', '0.500']),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the variant is default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the variant is non-default.
     */
    public function nonDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => false,
        ]);
    }

    /**
     * Indicate that the variant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the variant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the variant is soft-deleted.
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
