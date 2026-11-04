<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductDuplicator
{
    /**
     * Duplicate a product as an inactive draft with unique slug/SKUs.
     * Copies categories, attributes, variants, media files, and inventory thresholds (stock starts at 0).
     */
    public function duplicate(Product $product): Product
    {
        $product->load([
            'categories',
            'productAttributeValues',
            'media',
            'variants.variantAttributeValues',
            'variants.media',
            'variants.inventory',
        ]);

        return DB::transaction(function () use ($product): Product {
            $copy = $product->replicate(['slug']);
            $copy->name = $this->uniqueName($product->name);
            $copy->slug = $this->uniqueSlug(Str::slug($product->slug).'-copy');
            $copy->is_active = false;
            $copy->is_featured = false;
            $copy->save();

            $copy->categories()->sync($product->categories->pluck('id')->all());

            foreach ($product->productAttributeValues as $assignment) {
                $copy->productAttributeValues()->create([
                    'attribute_id' => $assignment->attribute_id,
                    'attribute_value_id' => $assignment->attribute_value_id,
                    'text_value' => $assignment->text_value,
                    'number_value' => $assignment->number_value,
                    'boolean_value' => $assignment->boolean_value,
                ]);
            }

            foreach ($product->orderedMedia()->get() as $media) {
                $this->copyMediaToOwner($media, $copy, "products/{$copy->id}");
            }

            foreach ($product->variants as $variant) {
                $this->duplicateVariant($variant, $copy);
            }

            return $copy->fresh(['variants', 'categories', 'media']);
        });
    }

    private function duplicateVariant(ProductVariant $variant, Product $copy): ProductVariant
    {
        $newVariant = $variant->replicate(['sku', 'barcode']);
        $newVariant->product_id = $copy->id;
        $newVariant->sku = $this->uniqueSku($variant->sku);
        $newVariant->barcode = null;
        $newVariant->save();

        foreach ($variant->variantAttributeValues as $assignment) {
            $newVariant->variantAttributeValues()->create([
                'attribute_id' => $assignment->attribute_id,
                'attribute_value_id' => $assignment->attribute_value_id,
                'text_value' => $assignment->text_value,
                'number_value' => $assignment->number_value,
                'boolean_value' => $assignment->boolean_value,
            ]);
        }

        foreach ($variant->orderedMedia()->get() as $media) {
            $this->copyMediaToOwner($media, $newVariant, "variants/{$newVariant->id}");
        }

        $threshold = (int) ($variant->inventory?->low_stock_threshold ?? 0);
        Inventory::createForVariant($newVariant, openingQuantity: 0, lowStockThreshold: $threshold);

        return $newVariant;
    }

    private function copyMediaToOwner(Media $media, Product|ProductVariant $owner, string $directory): void
    {
        $disk = $media->disk ?: 'public';
        $extension = pathinfo((string) $media->path, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = $directory.'/'.(string) Str::uuid().'.'.strtolower($extension);

        if (Storage::disk($disk)->exists($media->path)) {
            Storage::disk($disk)->copy($media->path, $newPath);
        } else {
            $newPath = $media->path;
        }

        $owner->addMedia([
            'disk' => $disk,
            'path' => $newPath,
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'alt_text' => $media->alt_text,
            'is_primary' => $media->is_primary,
            'sort_order' => $media->sort_order,
        ]);
    }

    private function uniqueName(string $name): string
    {
        $base = trim($name).' (Copy)';
        $candidate = $base;
        $i = 2;

        while (Product::withTrashed()->where('name', $candidate)->exists()) {
            $candidate = $base.' '.$i;
            $i++;
        }

        return $candidate;
    }

    private function uniqueSlug(string $base): string
    {
        $candidate = $base;
        $i = 2;

        while (Product::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function uniqueSku(string $sku): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_-]/', '', $sku) ?: 'SKU';
        $base = Str::limit($base, 40, '');
        $candidate = $base.'-COPY';
        $i = 2;

        while (ProductVariant::withTrashed()->where('sku', $candidate)->exists()) {
            $candidate = $base.'-COPY'.$i;
            $i++;
        }

        return $candidate;
    }
}
