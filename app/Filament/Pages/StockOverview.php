<?php

namespace App\Filament\Pages;

use App\Exceptions\InventoryException;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StockOverview extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Overview';

    protected static ?string $title = 'Stock Overview';

    protected static ?string $slug = 'inventory/stock-overview';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.stock-overview';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('inventory.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return array{
     *     total_variants: int,
     *     in_stock: int,
     *     low_stock: int,
     *     out_of_stock: int,
     *     not_initialized: int
     * }
     */
    public function getKpiSummary(): array
    {
        $aggregate = ProductVariant::query()
            ->leftJoin('inventories', 'inventories.product_variant_id', '=', 'product_variants.id')
            ->selectRaw('
                COUNT(product_variants.id) as total_variants,
                COUNT(CASE WHEN inventories.id IS NOT NULL AND inventories.quantity > inventories.low_stock_threshold THEN 1 END) as in_stock,
                COUNT(CASE WHEN inventories.id IS NOT NULL AND inventories.quantity > 0 AND inventories.quantity <= inventories.low_stock_threshold THEN 1 END) as low_stock,
                COUNT(CASE WHEN inventories.id IS NOT NULL AND inventories.quantity <= 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN inventories.id IS NULL THEN 1 END) as not_initialized
            ')
            ->first();

        return [
            'total_variants' => (int) ($aggregate?->total_variants ?? 0),
            'in_stock' => (int) ($aggregate?->in_stock ?? 0),
            'low_stock' => (int) ($aggregate?->low_stock ?? 0),
            'out_of_stock' => (int) ($aggregate?->out_of_stock ?? 0),
            'not_initialized' => (int) ($aggregate?->not_initialized ?? 0),
        ];
    }

    /**
     * @return array<int, array{label: string, value: int, color: string, icon: Heroicon}>
     */
    public function getKpiCards(): array
    {
        $summary = $this->getKpiSummary();

        return [
            [
                'label' => 'Total Variants',
                'value' => $summary['total_variants'],
                'color' => 'primary',
                'icon' => Heroicon::OutlinedSquares2x2,
            ],
            [
                'label' => 'In Stock',
                'value' => $summary['in_stock'],
                'color' => 'success',
                'icon' => Heroicon::OutlinedCheckCircle,
            ],
            [
                'label' => 'Low Stock',
                'value' => $summary['low_stock'],
                'color' => 'warning',
                'icon' => Heroicon::OutlinedExclamationTriangle,
            ],
            [
                'label' => 'Out of Stock',
                'value' => $summary['out_of_stock'],
                'color' => 'danger',
                'icon' => Heroicon::OutlinedXCircle,
            ],
            [
                'label' => 'Not Initialized',
                'value' => $summary['not_initialized'],
                'color' => 'gray',
                'icon' => Heroicon::OutlinedQuestionMarkCircle,
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductVariant::query()->with(['product', 'inventory'])
            )
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Product::select('name')->whereColumn('products.id', 'product_variants.product_id'),
                        $direction
                    )),

                TextColumn::make('name')
                    ->label('Variant')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('inventory.quantity')
                    ->label('Stock')
                    ->placeholder('—')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('quantity')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    )),

                TextColumn::make('inventory.low_stock_threshold')
                    ->label('Threshold')
                    ->placeholder('—')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('low_stock_threshold')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    )),

                TextColumn::make('stock_status')
                    ->label('Status')
                    ->state(function (ProductVariant $record): string {
                        $inventory = $record->inventory;
                        if (! $inventory) {
                            return 'Not Initialized';
                        }

                        if ($inventory->quantity <= 0) {
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
                        'Not Initialized' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('inventory.updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('updated_at')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    )),
            ])
            ->filters([
                SelectFilter::make('variant_status')
                    ->label('Variant Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->placeholder('All')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active' => $query->where('is_active', true),
                            'inactive' => $query->where('is_active', false),
                            default => $query,
                        };
                    }),

                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                        'not_initialized' => 'Not Initialized',
                    ])
                    ->placeholder('All')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'in_stock' => $query->whereHas('inventory', fn (Builder $q) => $q->whereColumn('quantity', '>', 'low_stock_threshold')),
                            'low_stock' => $query->whereHas('inventory', fn (Builder $q) => $q->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'low_stock_threshold')),
                            'out_of_stock' => $query->whereHas('inventory', fn (Builder $q) => $q->where('quantity', '<=', 0)),
                            'not_initialized' => $query->whereDoesntHave('inventory'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('viewProduct')
                    ->label('View Product')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (ProductVariant $record): string => ProductResource::getUrl('edit', ['record' => $record->product_id])),

                Action::make('adjustStock')
                    ->label('Adjust Stock')
                    ->icon(Heroicon::OutlinedArrowsUpDown)
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

                Action::make('stockHistory')
                    ->label('Stock History')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->modalHeading(fn (ProductVariant $record): string => 'Stock History: '.($record->name ? "{$record->name} ({$record->sku})" : $record->sku))
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->authorize(fn () => auth()->user()?->can('viewHistory', Inventory::class))
                    ->modalContent(fn (ProductVariant $record) => view('filament.resources.products.variant-stock-history-modal', ['variant' => $record])),
            ]);
    }
}
