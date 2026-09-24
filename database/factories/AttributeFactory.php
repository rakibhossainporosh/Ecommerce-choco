<?php

namespace Database\Factories;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uniqueId = fake()->unique()->numberBetween(1000, 999999);
        $name = 'Attribute '.$uniqueId;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => Attribute::ATTRIBUTE_TYPE_SELECT,
            'scope' => Attribute::SCOPE_PRODUCT,
            'description' => fake()->optional(0.6)->sentence(),
            'is_required' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function productScope(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => Attribute::SCOPE_PRODUCT,
        ]);
    }

    public function variantScope(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => Attribute::SCOPE_VARIANT,
        ]);
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Attribute::ATTRIBUTE_TYPE_TEXT,
        ]);
    }

    public function textarea(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Attribute::ATTRIBUTE_TYPE_TEXTAREA,
        ]);
    }

    public function number(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Attribute::ATTRIBUTE_TYPE_NUMBER,
        ]);
    }

    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Attribute::ATTRIBUTE_TYPE_BOOLEAN,
        ]);
    }

    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Attribute::ATTRIBUTE_TYPE_SELECT,
        ]);
    }

    public function multiselect(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Attribute::ATTRIBUTE_TYPE_MULTISELECT,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
