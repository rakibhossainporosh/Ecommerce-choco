<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttributeValue>
 */
class ProductAttributeValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attribute = Attribute::factory()->productScope()->select()->create();
        $attributeValue = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
        ]);

        return [
            'product_id' => Product::factory(),
            'attribute_id' => $attribute->id,
            'attribute_value_id' => $attributeValue->id,
            'text_value' => null,
            'number_value' => null,
            'boolean_value' => null,
        ];
    }

    public function forText(?string $text = 'Sample Text Value'): static
    {
        return $this->state(function (array $attributes) use ($text) {
            $attribute = Attribute::factory()->productScope()->text()->create();

            return [
                'attribute_id' => $attribute->id,
                'attribute_value_id' => null,
                'text_value' => $text,
                'number_value' => null,
                'boolean_value' => null,
            ];
        });
    }

    public function forTextarea(?string $text = 'Sample Textarea Description Value'): static
    {
        return $this->state(function (array $attributes) use ($text) {
            $attribute = Attribute::factory()->productScope()->textarea()->create();

            return [
                'attribute_id' => $attribute->id,
                'attribute_value_id' => null,
                'text_value' => $text,
                'number_value' => null,
                'boolean_value' => null,
            ];
        });
    }

    public function forNumber(float|int|string $number = 70.5): static
    {
        return $this->state(function (array $attributes) use ($number) {
            $attribute = Attribute::factory()->productScope()->number()->create();

            return [
                'attribute_id' => $attribute->id,
                'attribute_value_id' => null,
                'text_value' => null,
                'number_value' => $number,
                'boolean_value' => null,
            ];
        });
    }

    public function forBoolean(bool $bool = true): static
    {
        return $this->state(function (array $attributes) use ($bool) {
            $attribute = Attribute::factory()->productScope()->boolean()->create();

            return [
                'attribute_id' => $attribute->id,
                'attribute_value_id' => null,
                'text_value' => null,
                'number_value' => null,
                'boolean_value' => $bool,
            ];
        });
    }

    public function forSelect(?AttributeValue $value = null): static
    {
        return $this->state(function (array $attributes) use ($value) {
            if ($value) {
                return [
                    'attribute_id' => $value->attribute_id,
                    'attribute_value_id' => $value->id,
                    'text_value' => null,
                    'number_value' => null,
                    'boolean_value' => null,
                ];
            }

            $attribute = Attribute::factory()->productScope()->select()->create();
            $attributeValue = AttributeValue::factory()->create([
                'attribute_id' => $attribute->id,
            ]);

            return [
                'attribute_id' => $attribute->id,
                'attribute_value_id' => $attributeValue->id,
                'text_value' => null,
                'number_value' => null,
                'boolean_value' => null,
            ];
        });
    }

    public function forMultiselect(?AttributeValue $value = null): static
    {
        return $this->state(function (array $attributes) use ($value) {
            if ($value) {
                return [
                    'attribute_id' => $value->attribute_id,
                    'attribute_value_id' => $value->id,
                    'text_value' => null,
                    'number_value' => null,
                    'boolean_value' => null,
                ];
            }

            $attribute = Attribute::factory()->productScope()->multiselect()->create();
            $attributeValue = AttributeValue::factory()->create([
                'attribute_id' => $attribute->id,
            ]);

            return [
                'attribute_id' => $attribute->id,
                'attribute_value_id' => $attributeValue->id,
                'text_value' => null,
                'number_value' => null,
                'boolean_value' => null,
            ];
        });
    }
}
