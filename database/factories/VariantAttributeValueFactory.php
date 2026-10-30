<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use App\Models\VariantAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariantAttributeValue>
 */
class VariantAttributeValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attribute = Attribute::factory()->variantScope()->select()->create();
        $attributeValue = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
        ]);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'attribute_id' => $attribute->id,
            'attribute_value_id' => $attributeValue->id,
            'text_value' => null,
            'number_value' => null,
            'boolean_value' => null,
        ];
    }

    public function forText(?string $text = 'Sample Variant Text'): static
    {
        return $this->state(function (array $attributes) use ($text) {
            $attribute = Attribute::factory()->variantScope()->text()->create();

            return [
                'attribute_id' => $attribute->id,
                'attribute_value_id' => null,
                'text_value' => $text,
                'number_value' => null,
                'boolean_value' => null,
            ];
        });
    }

    public function forTextarea(?string $text = 'Sample Variant Textarea Description'): static
    {
        return $this->state(function (array $attributes) use ($text) {
            $attribute = Attribute::factory()->variantScope()->textarea()->create();

            return [
                'attribute_id' => $attribute->id,
                'attribute_value_id' => null,
                'text_value' => $text,
                'number_value' => null,
                'boolean_value' => null,
            ];
        });
    }

    public function forNumber(float|int|string $number = 150.0): static
    {
        return $this->state(function (array $attributes) use ($number) {
            $attribute = Attribute::factory()->variantScope()->number()->create();

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
            $attribute = Attribute::factory()->variantScope()->boolean()->create();

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

            $attribute = Attribute::factory()->variantScope()->select()->create();
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

            $attribute = Attribute::factory()->variantScope()->multiselect()->create();
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
