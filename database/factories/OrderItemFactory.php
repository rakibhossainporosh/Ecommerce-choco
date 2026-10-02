<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 100, 1000);
        $discount = 0.00;
        $lineTotal = round(($unitPrice * $quantity) - $discount, 2);

        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'product_name' => fake()->words(3, true),
            'variant_name' => fake()->randomElement(['100g Bar', '250g Box', 'Standard']),
            'sku' => 'SKU-'.fake()->unique()->bothify('??-####'),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'discount_amount' => $discount,
            'line_total' => $lineTotal,
        ];
    }

    /**
     * Configure snapshot values from an existing variant.
     */
    public function forVariant(ProductVariant $variant, int $quantity = 1, ?float $unitPrice = null): static
    {
        return $this->state(function (array $attributes) use ($variant, $quantity, $unitPrice) {
            $price = $unitPrice ?? (float) $variant->selling_price;
            $discount = 0.00;
            $lineTotal = round(($price * $quantity) - $discount, 2);

            return [
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product?->name ?? 'Product',
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'unit_price' => $price,
                'quantity' => $quantity,
                'discount_amount' => $discount,
                'line_total' => $lineTotal,
            ];
        });
    }
}
