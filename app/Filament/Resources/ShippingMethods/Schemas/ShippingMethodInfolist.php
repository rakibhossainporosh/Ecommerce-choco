<?php

namespace App\Filament\Resources\ShippingMethods\Schemas;

use App\Enums\ShippingProvider;
use App\Models\ShippingMethod;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ShippingMethodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $currencyFormat = fn ($state): string => config('currency.symbol', '৳').' '.number_format(
            (float) $state,
            (int) config('currency.decimals', 2),
            (string) config('currency.decimal_separator', '.'),
            (string) config('currency.thousands_separator', ',')
        );

        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Shipping Method Details')
                    ->icon(Heroicon::OutlinedTruck)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('code')->copyable(),
                        TextEntry::make('provider')
                            ->badge()
                            ->formatStateUsing(fn (?ShippingProvider $state): ?string => $state?->label()
                                ?? (is_string($state) ? ShippingProvider::tryFrom($state)?->label() : null)
                                ?? (string) $state
                            ),
                        TextEntry::make('charge')
                            ->formatStateUsing($currencyFormat),
                        TextEntry::make('free_shipping_threshold')
                            ->formatStateUsing(fn ($state) => $state ? $currencyFormat($state) : '—')
                            ->placeholder(__('—')),
                        TextEntry::make('delivery_estimate')
                            ->label('Delivery Window')
                            ->state(fn (ShippingMethod $record): string => $record->getEstimatedDeliveryText()),
                        IconEntry::make('is_active')->boolean(),
                        TextEntry::make('description')
                            ->placeholder(__('—'))
                            ->columnSpanFull(),
                        TextEntry::make('shipments_count')
                            ->label('Shipments')
                            ->state(fn (ShippingMethod $record): string => (string) $record->shipments()->count()),
                    ]),
            ]);
    }
}
