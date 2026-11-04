<?php

namespace App\Filament\Resources\Products\Actions;

use App\Exceptions\InventoryException;
use App\Models\Inventory;
use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class VariantInventoryActions
{
    public static function makeAdjustStockAction(): Action
    {
        return Action::make('adjustStock')
            ->label('Adjust Stock')
            ->icon(Heroicon::OutlinedArrowsUpDown)
            ->color('primary')
            ->modalHeading(fn (ProductVariant $record): string => __('Adjust Stock: :name', [
                'name' => $record->name ? "{$record->name} ({$record->sku})" : $record->sku,
            ]))
            ->modalWidth(Width::Medium)
            ->authorize(fn () => auth()->user()?->can('adjust', Inventory::class))
            ->form([
                Select::make('type')
                    ->label('Adjustment Type')
                    ->options([
                        'in' => __('Stock In'),
                        'out' => __('Stock Out'),
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
                    ->helperText(__('Enter a positive whole number.'))
                    ->rules(['required', 'integer', 'min:1']),

                Select::make('reason')
                    ->label('Reason')
                    ->options(fn (Get $get): array => match ($get('type')) {
                        'out' => [
                            'Damaged' => __('Damaged'),
                            'Correction' => __('Correction'),
                            'Expired' => __('Expired'),
                            'Lost' => __('Lost'),
                            'Other' => __('Other'),
                        ],
                        default => [
                            'Opening Stock' => __('Opening Stock'),
                            'Restock' => __('Restock'),
                            'Purchase' => __('Purchase'),
                            'Correction' => __('Correction'),
                            'Other' => __('Other'),
                        ],
                    })
                    ->required()
                    ->live(),

                TextInput::make('custom_reason')
                    ->label('Custom Reason')
                    ->placeholder(__('Specify custom reason'))
                    ->visible(fn (Get $get): bool => $get('reason') === 'Other')
                    ->required(fn (Get $get): bool => $get('reason') === 'Other')
                    ->maxLength(255),

                TextInput::make('note')
                    ->label('Note (Optional)')
                    ->placeholder(__('e.g., Received from supplier, damage notes, etc.'))
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
                    } elseif ($type === 'opening' || ($type === 'in' && $finalReason === 'Opening Stock')) {
                        $inventory->openStock($quantity, $finalReason, $note, auth()->user());
                    } elseif ($type === 'in') {
                        $inventory->adjustIn($quantity, $finalReason, $note, auth()->user());
                    } elseif ($type === 'out') {
                        $inventory->adjustOut($quantity, $finalReason, $note, auth()->user());
                    }

                    Notification::make()
                        ->title(__('Stock adjusted successfully.'))
                        ->body(__('New stock level: :quantity', ['quantity' => $inventory->fresh()->quantity]))
                        ->success()
                        ->send();
                } catch (InventoryException $e) {
                    Notification::make()
                        ->title(__('Stock Adjustment Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    $action->halt();
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()
                        ->title(__('Stock Adjustment Error'))
                        ->body(__('An unexpected error occurred while adjusting stock: :message', ['message' => $e->getMessage()]))
                        ->danger()
                        ->send();

                    $action->halt();
                }
            });
    }

    public static function makeSetThresholdAction(string $name = 'editThreshold'): Action
    {
        return Action::make($name)
            ->label('Set Threshold')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->modalHeading(fn (ProductVariant $record): string => __('Low Stock Threshold: :name', [
                'name' => $record->name ? "{$record->name} ({$record->sku})" : $record->sku,
            ]))
            ->modalWidth(Width::Medium)
            ->authorize(fn () => auth()->user()?->can('adjust', Inventory::class))
            ->fillForm(fn (ProductVariant $record): array => [
                'low_stock_threshold' => $record->inventory?->low_stock_threshold ?? 0,
            ])
            ->form([
                TextInput::make('low_stock_threshold')
                    ->label('Low Stock Threshold')
                    ->helperText(__('Alert threshold when stock drops to or below this level.'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required()
                    ->rules(['required', 'integer', 'min:0']),
            ])
            ->action(function (Action $action, ProductVariant $record, array $data): void {
                abort_unless(auth()->user()?->can('adjust', Inventory::class), 403, 'Unauthorized.');

                $threshold = max(0, (int) $data['low_stock_threshold']);

                try {
                    $inventory = $record->inventory()->first();
                    if (! $inventory) {
                        Inventory::createForVariant($record, lowStockThreshold: $threshold);
                    } else {
                        $inventory->update(['low_stock_threshold' => $threshold]);
                    }

                    Notification::make()
                        ->title(__('Threshold updated successfully.'))
                        ->body(__('Low stock alert threshold set to :quantity.', ['quantity' => $threshold]))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()
                        ->title(__('Threshold Update Error'))
                        ->body(__('An unexpected error occurred while updating threshold: :message', ['message' => $e->getMessage()]))
                        ->danger()
                        ->send();

                    $action->halt();
                }
            });
    }

    public static function makeStockHistoryAction(): Action
    {
        return Action::make('stockHistory')
            ->label('Stock History')
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->modalHeading(fn (ProductVariant $record): string => __('Stock History: :name', [
                'name' => $record->name ? "{$record->name} ({$record->sku})" : $record->sku,
            ]))
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->authorize(fn () => auth()->user()?->can('viewHistory', Inventory::class))
            ->modalContent(fn (ProductVariant $record) => view(
                'filament.resources.products.variant-stock-history-modal',
                ['variant' => $record],
            ));
    }
}
