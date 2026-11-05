<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    protected $model = WishlistItem::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'product_id' => Product::factory(),
        ];
    }
}
