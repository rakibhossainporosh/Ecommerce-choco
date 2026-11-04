<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Actions\ProductCatalogActions;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'brand',
                'categories',
                'variants.inventory',
                'media',
            ]))
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('Image')
                    ->state(function (Product $record): ?string {
                        $media = $record->getResolvedPrimaryMedia() ?? $record->defaultVariant?->getResolvedPrimaryMedia();

                        return $media?->url();
                    })
                    ->circular()
                    ->alignCenter(),

                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->alignCenter(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->state(fn (Product $record): string => $record->defaultVariant?->sku ?? $record->variants->first()?->sku ?? '—')
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('variants', fn ($q) => $q->where('sku', 'like', "%{$search}%")))
                    ->copyable()
                    ->placeholder(__('—'))
                    ->alignCenter(),

                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->separator(', ')
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('price')
                    ->label('Price')
                    ->state(function (Product $record): string {
                        $variants = $record->variants;
                        if ($variants->isEmpty()) {
                            return '—';
                        }

                        $min = $variants->min('selling_price');
                        $max = $variants->max('selling_price');

                        if ($min === null) {
                            return '—';
                        }

                        if ((float) $min === (float) $max) {
                            return '৳ '.number_format((float) $min, 2);
                        }

                        return '৳ '.number_format((float) $min, 2).' - ৳ '.number_format((float) $max, 2);
                    })
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy(
                        ProductVariant::select('selling_price')
                            ->whereColumn('product_variants.product_id', 'products.id')
                            ->where('is_default', true)
                            ->limit(1),
                        $direction
                    ))
                    ->alignCenter(),

                TextColumn::make('stock_status')
                    ->label('Stock')
                    ->state(function (Product $record): string {
                        $variants = $record->variants;

                        if ($variants->isEmpty()) {
                            return __('No Variants');
                        }

                        $totalStock = $variants->sum(fn ($v) => $v->inventory?->quantity ?? 0);

                        if ($totalStock <= 0) {
                            return __('Out of Stock (:count)', ['count' => 0]);
                        }

                        $hasLowStock = $variants->contains(fn ($v) => $v->inventory && $v->inventory->isLowStock());

                        if ($hasLowStock) {
                            return __('Low Stock (:count)', ['count' => $totalStock]);
                        }

                        return __('In Stock (:count)', ['count' => $totalStock]);
                    })
                    ->badge()
                    ->color(function (Product $record): string {
                        $variants = $record->variants;

                        if ($variants->isEmpty()) {
                            return 'gray';
                        }

                        $totalStock = $variants->sum(fn ($v) => $v->inventory?->quantity ?? 0);

                        if ($totalStock <= 0) {
                            return 'danger';
                        }

                        if ($variants->contains(fn ($v) => $v->inventory && $v->inventory->isLowStock())) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->alignCenter()
                    ->extraHeaderAttributes(['class' => 'fi-ta-header-cell-stock-status'])
                    ->extraCellAttributes(['class' => 'fi-ta-cell-stock-status']),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->placeholder(__('—'))
                    ->sortable()
                    ->toggleable()
                    ->alignCenter()
                    ->extraHeaderAttributes(['class' => 'fi-ta-header-cell-brand-name'])
                    ->extraCellAttributes(['class' => 'fi-ta-cell-brand-name']),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),

                TernaryFilter::make('is_featured')
                    ->label('Featured Status'),

                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name'),

                SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('categories', 'name'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ProductCatalogActions::makeDuplicateAction(),
                ProductCatalogActions::makeViewOnStoreAction(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activateSelected')
                        ->label(__('Publish selected'))
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('Publish selected products'))
                        ->modalDescription(__('Selected products will become active in the catalog (must already satisfy active-product rules).'))
                        ->authorize('update')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $ok = 0;
                            $failed = 0;

                            foreach ($records as $product) {
                                /** @var Product $product */
                                if (! auth()->user()?->can('update', $product)) {
                                    $failed++;

                                    continue;
                                }

                                try {
                                    $product->update(['is_active' => true]);
                                    $ok++;
                                } catch (\Throwable) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title(__('Bulk publish finished'))
                                ->body(__(':ok published, :failed skipped.', ['ok' => $ok, 'failed' => $failed]))
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('deactivateSelected')
                        ->label(__('Unpublish selected'))
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading(__('Unpublish selected products'))
                        ->authorize('update')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $ids = $records
                                ->filter(fn (Product $product): bool => auth()->user()?->can('update', $product) ?? false)
                                ->modelKeys();

                            Product::query()->whereIn('id', $ids)->update([
                                'is_active' => false,
                                'is_featured' => false,
                            ]);

                            Notification::make()
                                ->title(__('Selected products unpublished'))
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('featureSelected')
                        ->label(__('Feature selected'))
                        ->icon(Heroicon::OutlinedStar)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->authorize('update')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $ok = 0;
                            $failed = 0;

                            foreach ($records as $product) {
                                /** @var Product $product */
                                if (! auth()->user()?->can('update', $product)) {
                                    $failed++;

                                    continue;
                                }

                                try {
                                    DB::transaction(function () use ($product): void {
                                        if (! $product->is_active) {
                                            $product->update(['is_active' => true]);
                                        }
                                        $product->update(['is_featured' => true]);
                                    });
                                    $ok++;
                                } catch (\Throwable) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title(__('Bulk feature finished'))
                                ->body(__(':ok featured, :failed skipped.', ['ok' => $ok, 'failed' => $failed]))
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('exportSelected')
                        ->label(__('Export CSV'))
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->color('gray')
                        ->action(fn (Collection $records) => ProductCatalogActions::exportCsv($records)),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
