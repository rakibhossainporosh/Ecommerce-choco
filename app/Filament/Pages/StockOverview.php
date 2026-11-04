<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\TranslatesPageNavigation;
use App\Filament\Resources\Products\Actions\VariantInventoryActions;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
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
    use TranslatesPageNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Inventory / Stock';

    protected static ?string $title = 'Stock Overview';

    protected static ?string $slug = 'inventory/stock-overview';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.stock-overview';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('inventory.view');
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return [
            'fi-page-stock-overview',
        ];
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
                'filter' => null,
                'subtext' => 'All catalog variants',
            ],
            [
                'label' => 'In Stock',
                'value' => $summary['in_stock'],
                'color' => 'success',
                'icon' => Heroicon::OutlinedCheckCircle,
                'filter' => 'in_stock',
                'subtext' => 'Healthy stock level',
            ],
            [
                'label' => 'Low Stock',
                'value' => $summary['low_stock'],
                'color' => 'warning',
                'icon' => Heroicon::OutlinedExclamationTriangle,
                'filter' => 'low_stock',
                'subtext' => 'Reorder alert threshold',
            ],
            [
                'label' => 'Out of Stock',
                'value' => $summary['out_of_stock'],
                'color' => 'danger',
                'icon' => Heroicon::OutlinedXCircle,
                'filter' => 'out_of_stock',
                'subtext' => '0 items available',
            ],
            [
                'label' => 'Not Initialized',
                'value' => $summary['not_initialized'],
                'color' => 'gray',
                'icon' => Heroicon::OutlinedQuestionMarkCircle,
                'filter' => 'not_initialized',
                'subtext' => 'Opening stock required',
            ],
        ];
    }

    public function filterByKpi(?string $status): void
    {
        $this->tableFilters['stock_status']['value'] = $status;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(__('Product Variant'))
            ->pluralModelLabel(__('Product Variants'))
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
                    ->placeholder(__('—'))
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('inventory.quantity')
                    ->label('Stock')
                    ->placeholder(__('—'))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('quantity')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    ))
                    ->alignCenter(),

                TextColumn::make('inventory.low_stock_threshold')
                    ->label('Threshold')
                    ->placeholder(__('—'))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('low_stock_threshold')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    ))
                    ->alignCenter(),

                TextColumn::make('stock_status')
                    ->label('Status')
                    ->state(function (ProductVariant $record): string {
                        $inventory = $record->inventory;

                        if (! $inventory) {
                            return __('Not Initialized');
                        }

                        if ($inventory->quantity <= 0) {
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

                        if (! $inventory) {
                            return 'gray';
                        }

                        if ($inventory->quantity <= 0) {
                            return 'danger';
                        }

                        if ($inventory->quantity <= $inventory->low_stock_threshold) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->alignCenter(),

                TextColumn::make('inventory.updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->placeholder(__('—'))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        Inventory::select('updated_at')->whereColumn('inventories.product_variant_id', 'product_variants.id'),
                        $direction
                    ))
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('variant_status')
                    ->label('Variant Status')
                    ->options([
                        'active' => __('Active'),
                        'inactive' => __('Inactive'),
                    ])
                    ->placeholder(__('All'))
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
                        'in_stock' => __('In Stock'),
                        'low_stock' => __('Low Stock'),
                        'out_of_stock' => __('Out of Stock'),
                        'not_initialized' => __('Not Initialized'),
                    ])
                    ->placeholder(__('All'))
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
                    ->url(fn (ProductVariant $record): string => ProductResource::getUrl('view', ['record' => $record->product_id])),

                VariantInventoryActions::makeAdjustStockAction(),
                VariantInventoryActions::makeSetThresholdAction('setThreshold'),
                VariantInventoryActions::makeStockHistoryAction(),
            ]);
    }
}
