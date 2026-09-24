<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::random(16).'.jpg';

        return [
            'mediable_type' => Product::class,
            'mediable_id' => 1,
            'disk' => 'public',
            'path' => 'products/'.$filename,
            'original_name' => 'chocolate-product.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(50000, 2000000),
            'width' => 1200,
            'height' => 1200,
            'alt_text' => fake()->sentence(4),
            'sort_order' => 0,
            'is_primary' => false,
        ];
    }

    /**
     * Associate the media with a specific Product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'mediable_type' => $product->getMorphClass(),
            'mediable_id' => $product->id,
            'path' => 'products/'.($attributes['path'] ?? Str::random(16).'.jpg'),
        ]);
    }

    /**
     * Associate the media with a specific ProductVariant.
     */
    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes) => [
            'mediable_type' => $variant->getMorphClass(),
            'mediable_id' => $variant->id,
            'path' => 'variants/'.($attributes['path'] ?? Str::random(16).'.jpg'),
        ]);
    }

    /**
     * Mark the media as primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
