<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Attribute;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->getRecord();
        $product->loadMissing(['productAttributeValues.attribute']);

        $assignments = [];
        foreach ($product->productAttributeValues as $pav) {
            $attrId = $pav->attribute_id;
            $type = $pav->attribute?->type;

            if ($type === Attribute::ATTRIBUTE_TYPE_MULTISELECT) {
                $assignments[$attrId][] = $pav->attribute_value_id;
            } elseif ($type === Attribute::ATTRIBUTE_TYPE_SELECT) {
                $assignments[$attrId] = $pav->attribute_value_id;
            } elseif ($type === Attribute::ATTRIBUTE_TYPE_TEXT || $type === Attribute::ATTRIBUTE_TYPE_TEXTAREA) {
                $assignments[$attrId] = $pav->text_value;
            } elseif ($type === Attribute::ATTRIBUTE_TYPE_NUMBER) {
                $assignments[$attrId] = $pav->number_value;
            } elseif ($type === Attribute::ATTRIBUTE_TYPE_BOOLEAN) {
                $assignments[$attrId] = (bool) $pav->boolean_value;
            }
        }

        $data['product_attributes'] = $assignments;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $productAttributes = $data['product_attributes'] ?? [];
        unset($data['product_attributes']);

        $record->update($data);

        $syncPayload = [];
        foreach ($productAttributes as $attrId => $val) {
            if ($val !== null && $val !== '' && $val !== []) {
                $syncPayload[$attrId] = $val;
            }
        }

        /** @var Product $record */
        $record->syncAttributes($syncPayload);

        return $record;
    }
}
