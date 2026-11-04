<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Concerns\TranslatesRelationManagerTitle;
use App\Filament\Resources\Products\Actions\VariantInventoryActions;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Models\Attribute;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Unit;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VariantsRelationManager extends RelationManager
{
    use TranslatesRelationManagerTitle;

    protected static string $relationship = 'variants';

    protected static ?string $title = 'Product Variants';

    protected static ?string $modelLabel = 'Product Variant';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Variant Name (Optional)')
                    ->placeholder(__('e.g. Standard, 100g Bar'))
                    ->nullable()
                    ->maxLength(255),

                Select::make('unit_id')
                    ->label('Unit')
                    ->options(fn () => Unit::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (blank($value)) {
                                return;
                            }

                            $unit = Unit::withTrashed()->find($value);
                            if (! $unit || $unit->trashed() || ! $unit->is_active) {
                                $fail(__('Cannot assign an invalid, inactive, or soft-deleted unit.'));
                            }
                        },
                    ]),

                TextInput::make('sku')
                    ->label('Variant SKU')
                    ->required()
                    ->maxLength(255)
                    ->unique(table: 'product_variants', column: 'sku', ignoreRecord: true)
                    ->rules(['alpha_dash']),

                TextInput::make('barcode')
                    ->label('Barcode (Optional)')
                    ->nullable()
                    ->maxLength(255)
                    ->unique(table: 'product_variants', column: 'barcode', ignoreRecord: true),

                TextInput::make('cost_price')
                    ->label('Cost Price')
                    ->numeric()
                    ->prefix('৳')
                    ->required()
                    ->minValue(0),

                TextInput::make('selling_price')
                    ->label('Selling Price')
                    ->numeric()
                    ->prefix('৳')
                    ->required()
                    ->minValue(0),

                TextInput::make('compare_at_price')
                    ->label('Compare at Price')
                    ->numeric()
                    ->prefix('৳')
                    ->nullable()
                    ->minValue(0)
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if ($value !== null && $value !== '') {
                                $selling = $get('selling_price');
                                if ($selling !== null && $selling !== '' && (float) $value < (float) $selling) {
                                    $fail(__('The compare at price must be greater than or equal to the selling price.'));
                                }
                            }
                        },
                    ]),

                TextInput::make('unit_quantity')
                    ->label('Unit Quantity')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(0.001),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Section::make('Variant Attributes')
                    ->description(__('Configure dynamic attributes for this variant.'))
                    ->components(fn () => ProductForm::getVariantAttributeComponents('variant_attributes')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['inventory', 'unit']))
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('Image')
                    ->alignCenter()
                    ->state(fn (ProductVariant $record): ?string => $record->getResolvedPrimaryMedia()?->url())
                    ->circular(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Variant Name')
                    ->alignCenter()
                    ->placeholder(__('—'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('media_status')
                    ->label('Media')
                    ->alignCenter()
                    ->state(function (ProductVariant $record): string {
                        $count = $record->media()->count();

                        return $count > 0 ? "{$count} image(s)" : 'Fallback (Product)';
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, 'Fallback') ? 'gray' : 'info'),

                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->alignCenter()
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('inventory.quantity')
                    ->label('Stock')
                    ->alignCenter()
                    ->placeholder(__('0'))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('quantity')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    ))
                    ->visible(fn () => auth()->user()?->can('viewAny', Inventory::class)),

                TextColumn::make('inventory.low_stock_threshold')
                    ->label('Threshold')
                    ->alignCenter()
                    ->placeholder(__('0'))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('low_stock_threshold')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    ))
                    ->visible(fn () => auth()->user()?->can('viewAny', Inventory::class)),

                TextColumn::make('stock_status')
                    ->label('Status')
                    ->alignCenter()
                    ->state(function (ProductVariant $record): string {
                        $inventory = $record->inventory;

                        if (! $inventory || $inventory->quantity <= 0) {
                            return __('Out of Stock');
                        }

                        if ($inventory->quantity <= $inventory->low_stock_threshold) {
                            return __('Low Stock');
                        }

                        return __('In Stock');
                    })
                    ->badge()
                    ->color(function (ProductVariant $record): string {
                        $inventory = $record->inventory;

                        if (! $inventory || $inventory->quantity <= 0) {
                            return 'danger';
                        }

                        if ($inventory->quantity <= $inventory->low_stock_threshold) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->visible(fn () => auth()->user()?->can('viewAny', Inventory::class)),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, RelationManager $livewire): Model {
                        return DB::transaction(function () use ($data, $livewire) {
                            $variantAttributes = $data['variant_attributes'] ?? [];
                            unset($data['variant_attributes']);

                            /** @var ProductVariant $variant */
                            $variant = $livewire->getOwnerRecord()->variants()->create($data);

                            $syncPayload = [];
                            foreach ($variantAttributes as $attrId => $val) {
                                if ($val !== null && $val !== '' && $val !== []) {
                                    $syncPayload[$attrId] = $val;
                                }
                            }

                            $variant->syncAttributes($syncPayload);

                            return $variant;
                        });
                    }),
            ])
            ->recordActions([
                Action::make('media')
                    ->label('Media')
                    ->icon('heroicon-o-photo')
                    ->badge(fn (ProductVariant $record): ?int => $record->media()->count() ?: null)
                    ->color(fn (ProductVariant $record): string => $record->media()->count() > 0 ? 'success' : 'gray')
                    ->modalHeading(fn (ProductVariant $record): string => __('Variant Media: :name', ['name' => $record->name ? "{$record->name} ({$record->sku})" : $record->sku]))
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (ProductVariant $record) => view('filament.resources.products.variant-media-modal', ['variant' => $record])),

                VariantInventoryActions::makeAdjustStockAction(),
                VariantInventoryActions::makeSetThresholdAction('editThreshold'),
                VariantInventoryActions::makeStockHistoryAction(),

                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, ProductVariant $record): array {
                        $record->loadMissing(['variantAttributeValues.attribute']);

                        $assignments = [];
                        foreach ($record->variantAttributeValues as $vav) {
                            $attrId = $vav->attribute_id;
                            $type = $vav->attribute?->type;

                            if ($type === Attribute::ATTRIBUTE_TYPE_MULTISELECT) {
                                $assignments[$attrId][] = $vav->attribute_value_id;
                            } elseif ($type === Attribute::ATTRIBUTE_TYPE_SELECT) {
                                $assignments[$attrId] = $vav->attribute_value_id;
                            } elseif ($type === Attribute::ATTRIBUTE_TYPE_TEXT || $type === Attribute::ATTRIBUTE_TYPE_TEXTAREA) {
                                $assignments[$attrId] = $vav->text_value;
                            } elseif ($type === Attribute::ATTRIBUTE_TYPE_NUMBER) {
                                $assignments[$attrId] = $vav->number_value;
                            } elseif ($type === Attribute::ATTRIBUTE_TYPE_BOOLEAN) {
                                $assignments[$attrId] = (bool) $vav->boolean_value;
                            }
                        }

                        $data['variant_attributes'] = $assignments;

                        return $data;
                    })
                    ->using(function (ProductVariant $record, array $data): Model {
                        return DB::transaction(function () use ($record, $data) {
                            $variantAttributes = $data['variant_attributes'] ?? [];
                            unset($data['variant_attributes']);

                            $record->update($data);

                            $syncPayload = [];
                            foreach ($variantAttributes as $attrId => $val) {
                                if ($val !== null && $val !== '' && $val !== []) {
                                    $syncPayload[$attrId] = $val;
                                }
                            }

                            $record->syncAttributes($syncPayload);

                            return $record;
                        });
                    }),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, ProductVariant $record): void {
                        if (! $record->canBeDeleted()) {
                            Notification::make()
                                ->danger()
                                ->title(__('Cannot delete this variant because it is the only variant or active default.'))
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
