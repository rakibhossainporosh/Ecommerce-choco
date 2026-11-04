<?php

namespace App\Filament\Resources\ShippingMethods\Tables;

use App\Enums\ShippingProvider;
use App\Models\ShippingMethod;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ShippingMethodsTable
{
    public static function configure(Table $table): Table
    {
        $currencyFormat = fn ($state): string => config('currency.symbol', '৳').' '.number_format(
            (float) $state,
            (int) config('currency.decimals', 2),
            (string) config('currency.decimal_separator', '.'),
            (string) config('currency.thousands_separator', ',')
        );

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Method / Zone')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('provider')
                    ->label('Courier')
                    ->badge()
                    ->formatStateUsing(fn (?ShippingProvider $state): ?string => $state?->label()
                        ?? (is_string($state) ? ShippingProvider::tryFrom($state)?->label() : null)
                        ?? (string) $state
                    )
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('charge')
                    ->label('Shipping Fee')
                    ->formatStateUsing($currencyFormat)
                    ->sortable()
                    ->weight('bold')
                    ->alignCenter(),

                TextColumn::make('free_shipping_threshold')
                    ->label('Free Above')
                    ->formatStateUsing(fn ($state) => $state ? $currencyFormat($state) : '—')
                    ->placeholder(__('—'))
                    ->alignCenter(),

                TextColumn::make('delivery_estimate')
                    ->label('Delivery Window')
                    ->state(fn (ShippingMethod $record): string => $record->getEstimatedDeliveryText())
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('shipments_count')
                    ->label('Shipments')
                    ->counts('shipments')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('charge', 'asc')
            ->filters([
                SelectFilter::make('provider')
                    ->options(ShippingProvider::class),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->recordActions([
                EditAction::make(),

                DeleteAction::make()
                    ->before(function (DeleteAction $action, ShippingMethod $record): void {
                        if ($record->shipments()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title(__('Cannot delete shipping method.'))
                                ->body(__('This method is referenced by :count historical shipments.', ['count' => $record->shipments()->count()]))
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
