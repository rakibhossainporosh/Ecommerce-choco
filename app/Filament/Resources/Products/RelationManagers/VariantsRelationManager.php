<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Exceptions\InventoryException;
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
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Product Variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Variant Name (Optional)')
                    ->placeholder('e.g. Standard, 100g Bar')
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
                                $fail('Cannot assign an invalid, inactive, or soft-deleted unit.');
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
                    ->label('Cost Price (BDT)')
                    ->numeric()
                    ->prefix('BDT')
                    ->required()
                    ->minValue(0),

                TextInput::make('selling_price')
                    ->label('Selling Price (BDT)')
                    ->numeric()
                    ->prefix('BDT')
                    ->required()
                    ->minValue(0),

                TextInput::make('compare_at_price')
                    ->label('Compare at Price (BDT)')
                    ->numeric()
                    ->prefix('BDT')
                    ->nullable()
                    ->minValue(0)
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if ($value !== null && $value !== '') {
                                $selling = $get('selling_price');
                                if ($selling !== null && $selling !== '' && (float) $value < (float) $selling) {
                                    $fail('The compare at price must be greater than or equal to the selling price.');
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
                    ->description('Configure dynamic attributes for this variant.')
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
                    ->disk('public')
                    ->state(fn (ProductVariant $record): ?string => $record->getResolvedPrimaryMedia()?->path)
                    ->circular(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Variant Name')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('media_status')
                    ->label('Media')
                    ->state(function (ProductVariant $record): string {
                        $count = $record->media()->count();

                        return $count > 0 ? "{$count} image(s)" : 'Fallback (Product)';
                    })
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, 'Fallback') ? 'gray' : 'info'),

                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->sortable(),

                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('inventory.quantity')
                    ->label('Stock')
                    ->placeholder('0')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('quantity')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    ))
                    ->visible(fn () => auth()->user()?->can('viewAny', Inventory::class)),

                TextColumn::make('inventory.low_stock_threshold')
                    ->label('Threshold')
                    ->placeholder('0')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('low_stock_threshold')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    ))
                    ->visible(fn () => auth()->user()?->can('viewAny', Inventory::class)),

                TextColumn::make('stock_status')
                    ->label('Status')
                    ->state(function (ProductVariant $record): string {
                        $inventory = $record->inventory;
                        if (! $inventory || $inventory->quantity <= 0) {
                            return 'Out of Stock';
                        }

                        if ($inventory->quantity <= $inventory->low_stock_threshold) {
                            return 'Low Stock';
                        }

                        return 'In Stock';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'In Stock' => 'success',
                        'Low Stock' => 'warning',
                        'Out of Stock' => 'danger',
                        default => 'gray',
                    })
                    ->visible(fn () => auth()->user()?->can('viewAny', Inventory::class)),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
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
                    ->modalHeading(fn (ProductVariant $record): string => 'Variant Media: '.($record->name ? "{$record->name} ({$record->sku})" : $record->sku))
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (ProductVariant $record) => view('filament.resources.products.variant-media-modal', ['variant' => $record])),

                Action::make('adjustStock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('primary')
                    ->modalHeading(fn (ProductVariant $record): string => 'Adjust Stock: '.($record->name ? "{$record->name} ({$record->sku})" : $record->sku))
                    ->modalWidth(Width::Medium)
                    ->authorize(fn () => auth()->user()?->can('adjust', Inventory::class))
                    ->form([
                        Select::make('type')
                            ->label('Adjustment Type')
                            ->options([
                                'in' => 'Stock In',
                                'out' => 'Stock Out',
                            ])
                            ->default('in')
                            ->required()
                            ->live(),

                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->helperText('Enter a positive whole number.')
                            ->rules(['required', 'integer', 'min:1']),

                        Select::make('reason')
                            ->label('Reason')
                            ->options(fn (Get $get): array => match ($get('type')) {
                                'out' => [
                                    'Damaged' => 'Damaged',
                                    'Correction' => 'Correction',
                                    'Expired' => 'Expired',
                                    'Lost' => 'Lost',
                                    'Other' => 'Other',
                                ],
                                default => [
                                    'Opening Stock' => 'Opening Stock',
                                    'Restock' => 'Restock',
                                    'Purchase' => 'Purchase',
                                    'Correction' => 'Correction',
                                    'Other' => 'Other',
                                ],
                            })
                            ->required()
                            ->live(),

                        TextInput::make('custom_reason')
                            ->label('Custom Reason')
                            ->placeholder('Specify custom reason')
                            ->visible(fn (Get $get): bool => $get('reason') === 'Other')
                            ->required(fn (Get $get): bool => $get('reason') === 'Other')
                            ->maxLength(255),

                        TextInput::make('note')
                            ->label('Note (Optional)')
                            ->placeholder('e.g., Received from supplier, damage notes, etc.')
                            ->nullable()
                            ->maxLength(1000),
                    ])
                    ->action(function (Action $action, ProductVariant $record, array $data): void {
                        abort_unless(auth()->user()?->can('adjust', Inventory::class), 403, 'Unauthorized.');

                        $quantity = (int) $data['quantity'];
                        $type = $data['type'] ?? 'in';
                        $rawReason = $data['reason'] ?? '';
                        $finalReason = ($rawReason === 'Other' && ! empty($data['custom_reason']))
                            ? (string) $data['custom_reason']
                            : (string) $rawReason;
                        $note = ! empty($data['note']) ? (string) $data['note'] : null;

                        try {
                            $inventory = $record->inventory()->first();

                            if (! $inventory) {
                                if ($type === 'opening' || ($type === 'in' && $finalReason === 'Opening Stock')) {
                                    $inventory = Inventory::createForVariant(
                                        variant: $record,
                                        openingQuantity: $quantity,
                                        reason: $finalReason,
                                        note: $note,
                                        createdBy: auth()->user(),
                                    );
                                } else {
                                    $inventory = Inventory::createForVariant($record);
                                    if ($type === 'in') {
                                        $inventory->adjustIn($quantity, $finalReason, $note, auth()->user());
                                    } else {
                                        $inventory->adjustOut($quantity, $finalReason, $note, auth()->user());
                                    }
                                }
                            } else {
                                if ($type === 'opening' || ($type === 'in' && $finalReason === 'Opening Stock')) {
                                    $inventory->openStock($quantity, $finalReason, $note, auth()->user());
                                } elseif ($type === 'in') {
                                    $inventory->adjustIn($quantity, $finalReason, $note, auth()->user());
                                } elseif ($type === 'out') {
                                    $inventory->adjustOut($quantity, $finalReason, $note, auth()->user());
                                }
                            }

                            Notification::make()
                                ->title('Stock adjusted successfully.')
                                ->body("New stock level: {$inventory->fresh()->quantity}")
                                ->success()
                                ->send();
                        } catch (InventoryException $e) {
                            Notification::make()
                                ->title('Stock Adjustment Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Stock Adjustment Error')
                                ->body('An unexpected error occurred while adjusting stock: '.$e->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),

                Action::make('editThreshold')
                    ->label('Set Threshold')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('gray')
                    ->modalHeading(fn (ProductVariant $record): string => 'Low Stock Threshold: '.($record->name ? "{$record->name} ({$record->sku})" : $record->sku))
                    ->modalWidth(Width::Medium)
                    ->authorize(fn () => auth()->user()?->can('adjust', Inventory::class))
                    ->fillForm(fn (ProductVariant $record): array => [
                        'low_stock_threshold' => $record->inventory?->low_stock_threshold ?? 0,
                    ])
                    ->form([
                        TextInput::make('low_stock_threshold')
                            ->label('Low Stock Threshold')
                            ->helperText('Alert threshold when stock drops to or below this level.')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->rules(['required', 'integer', 'min:0']),
                    ])
                    ->action(function (Action $action, ProductVariant $record, array $data): void {
                        abort_unless(auth()->user()?->can('adjust', Inventory::class), 403, 'Unauthorized.');

                        $threshold = (int) $data['low_stock_threshold'];

                        try {
                            $inventory = $record->inventory()->first();
                            if (! $inventory) {
                                Inventory::createForVariant($record, lowStockThreshold: $threshold);
                            } else {
                                $inventory->update(['low_stock_threshold' => $threshold]);
                            }

                            Notification::make()
                                ->title('Low stock threshold updated successfully.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()
                                ->title('Threshold Update Error')
                                ->body('An unexpected error occurred while updating threshold: '.$e->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),

                Action::make('stockHistory')
                    ->label('Stock History')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading(fn (ProductVariant $record): string => 'Stock History: '.($record->name ? "{$record->name} ({$record->sku})" : $record->sku))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->authorize(fn () => auth()->user()?->can('viewHistory', Inventory::class))
                    ->modalContent(fn (ProductVariant $record) => view('filament.resources.products.variant-stock-history-modal', ['variant' => $record])),

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
                                ->title('Cannot delete this variant because it is the only variant or active default.')
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
