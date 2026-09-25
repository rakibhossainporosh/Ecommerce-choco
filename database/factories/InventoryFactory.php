<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Inventory>
     */
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'quantity' => 0,
            'low_stock_threshold' => 0,
        ];
    }

    /**
     * Indicate that the inventory has in-stock quantity.
     */
    public function inStock(int $quantity = 50, int $lowStockThreshold = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'low_stock_threshold' => $lowStockThreshold,
        ]);
    }

    /**
     * Indicate that the inventory is out of stock.
     */
    public function outOfStock(int $lowStockThreshold = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'low_stock_threshold' => $lowStockThreshold,
        ]);
    }

    /**
     * Indicate that the inventory is in low-stock status.
     */
    public function lowStock(int $lowStockThreshold = 10, int $quantity = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'low_stock_threshold' => $lowStockThreshold,
        ]);
    }
}
