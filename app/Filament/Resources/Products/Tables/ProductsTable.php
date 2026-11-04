<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
