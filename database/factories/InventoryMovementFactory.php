<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<InventoryMovement>
     */
    protected $model = InventoryMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = 50;

        return [
            'inventory_id' => Inventory::factory(),
            'product_variant_id' => function (array $attributes) {
                if (isset($attributes['inventory_id'])) {
                    $inventory = Inventory::find($attributes['inventory_id']);
                    if ($inventory !== null) {
                        return $inventory->product_variant_id;
                    }
                }

                return ProductVariant::factory();
            },
            'type' => InventoryMovementType::Opening,
            'quantity' => $quantity,
            'quantity_before' => 0,
            'quantity_after' => $quantity,
            'reference_type' => null,
            'reference_id' => null,
            'reason' => 'Initial stock intake',
            'note' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * State for opening stock movement.
     */
    public function opening(int $quantity = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InventoryMovementType::Opening,
            'quantity' => $quantity,
            'quantity_before' => 0,
            'quantity_after' => $quantity,
            'reason' => 'Initial opening inventory balance',
        ]);
    }

    /**
     * State for stock adjustment in.
     */
    public function adjustmentIn(int $quantity = 10, int $quantityBefore = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InventoryMovementType::AdjustmentIn,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityBefore + $quantity,
            'reason' => 'Inventory adjustment addition',
        ]);
    }

    /**
     * State for stock adjustment out.
     */
    public function adjustmentOut(int $quantity = 10, int $quantityBefore = 50): static
    {
        $safeQuantity = min($quantity, $quantityBefore);

        return $this->state(fn (array $attributes) => [
            'type' => InventoryMovementType::AdjustmentOut,
            'quantity' => $safeQuantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityBefore - $safeQuantity,
            'reason' => 'Inventory adjustment reduction',
        ]);
    }
}
