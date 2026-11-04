<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Actions\ProductCatalogActions;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $extraBodyAttributes = [
        'class' => 'fi-unified-record-form-page fi-product-view-page',
    ];

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'brand',
            'categories',
            'media',
            'defaultVariant',
            'variants.inventory',
        ]);
    }

    public function getTitle(): string
    {
        /** @var Product $record */
        $record = $this->getRecord();

        return $record->name;
    }

    public function getSubheading(): ?string
    {
        return __('Catalog product details');
    }

    protected function getHeaderActions(): array
    {
        return [
            ProductCatalogActions::makeViewOnStoreAction(),
            ProductCatalogActions::makeDuplicateAction(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
