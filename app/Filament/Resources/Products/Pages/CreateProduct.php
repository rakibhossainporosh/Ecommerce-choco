<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $variantData = [
                'name' => $data['variant_name'] ?? null,
                'unit_id' => $data['unit_id'],
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'cost_price' => $data['cost_price'],
                'selling_price' => $data['selling_price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'unit_quantity' => $data['unit_quantity'],
                'is_default' => true,
                'is_active' => true,
            ];

            $productAttributes = $data['product_attributes'] ?? [];
            $variantAttributes = $data['variant_attributes'] ?? [];

            unset(
                $data['variant_name'],
                $data['unit_id'],
                $data['sku'],
                $data['barcode'],
                $data['cost_price'],
                $data['selling_price'],
                $data['compare_at_price'],
                $data['unit_quantity'],
                $data['product_attributes'],
                $data['variant_attributes']
            );

            /** @var Product $product */
            $product = static::getModel()::create($data);

            $productSyncPayload = [];
            foreach ($productAttributes as $attrId => $val) {
                if ($val !== null && $val !== '' && $val !== []) {
                    $productSyncPayload[$attrId] = $val;
                }
            }
            $product->syncAttributes($productSyncPayload);

            $variantData['product_id'] = $product->id;
            /** @var ProductVariant $variant */
            $variant = ProductVariant::create($variantData);

            $variantSyncPayload = [];
            foreach ($variantAttributes as $attrId => $val) {
                if ($val !== null && $val !== '' && $val !== []) {
                    $variantSyncPayload[$attrId] = $val;
                }
            }
            $variant->syncAttributes($variantSyncPayload);

            return $product;
        });
    }
}
