<?php

namespace App\Filament\Resources\Products\Actions;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\ProductDuplicator;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCatalogActions
{
    public static function makeViewOnStoreAction(): Action
    {
        return Action::make('viewOnStore')
            ->label(__('View on store'))
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (Product $record): string => route('product.show', $record->slug), shouldOpenInNewTab: true)
            ->visible(fn (?Product $record): bool => $record instanceof Product && filled($record->slug));
    }

    public static function makeDuplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label(__('Duplicate'))
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('Duplicate product'))
            ->modalDescription(__('Creates an inactive draft copy with unique slug and SKUs. Stock starts at 0; media and variants are copied.'))
            ->authorize('create')
            ->action(function (Product $record, Action $action): void {
                abort_unless(auth()->user()?->can('create', Product::class), 403);

                $copy = app(ProductDuplicator::class)->duplicate($record);

                Notification::make()
                    ->title(__('Product duplicated'))
                    ->body(__('Draft copy ready: :name', ['name' => $copy->name]))
                    ->success()
                    ->send();

                $action->redirect(ProductResource::getUrl('edit', ['record' => $copy]));
            });
    }

    /**
     * @param  Collection<int, Product>|array<int, Product>  $products
     */
    public static function exportCsv(Collection|array $products): StreamedResponse
    {
        $rows = Collection::wrap($products)->loadMissing(['brand', 'categories', 'defaultVariant', 'variants.inventory']);

        $filename = 'products-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Name',
                'Slug',
                'Brand',
                'Categories',
                'Default SKU',
                'Price',
                'Stock',
                'Active',
                'Featured',
                'Created At',
            ]);

            foreach ($rows as $product) {
                /** @var Product $product */
                $price = $product->defaultVariant?->selling_price;
                $stock = (int) $product->variants->sum(fn ($variant) => $variant->inventory?->quantity ?? 0);

                fputcsv($handle, [
                    $product->id,
                    $product->name,
                    $product->slug,
                    $product->brand?->name,
                    $product->categories->pluck('name')->implode(' | '),
                    $product->defaultVariant?->sku,
                    $price !== null ? number_format((float) $price, 2, '.', '') : '',
                    $stock,
                    $product->is_active ? '1' : '0',
                    $product->is_featured ? '1' : '0',
                    optional($product->created_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
