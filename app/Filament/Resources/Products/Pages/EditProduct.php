<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\HasDescriptiveNotifications;
use App\Filament\Resources\Products\Actions\ProductCatalogActions;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Attribute;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    use HasDescriptiveNotifications;

    protected static string $resource = ProductResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-unified-record-form-page',
    ];

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            ProductCatalogActions::makeViewOnStoreAction(),
            ProductCatalogActions::makeDuplicateAction(),
            ViewAction::make()
                ->label(__('View details'))
                ->icon(Heroicon::OutlinedEye),
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
