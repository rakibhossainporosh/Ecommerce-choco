<?php

namespace App\Filament\Resources\Shipments\Schemas;

use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Shipment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShipmentInfolist
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
            ->components([
                Section::make('Consignment & Carrier')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 4,
                    ])
                    ->schema([
                        TextEntry::make('shipment_number')
                            ->label('Shipment #')
                            ->weight('bold')
                            ->copyable(),

                        TextEntry::make('order.order_number')
                            ->label('Associated Order')
                            ->weight('bold')
                            ->copyable(),

                        TextEntry::make('provider')
                            ->label('Courier Partner')
                            ->badge()
                            ->formatStateUsing(fn (?ShippingProvider $state): ?string => $state?->label()
                                ?? (is_string($state) ? ShippingProvider::tryFrom($state)?->label() : null)
                                ?? (string) $state
                            ),

                        TextEntry::make('status')
                            ->label('Delivery Status')
                            ->badge()
                            ->color(fn ($state): string => match ($state instanceof ShipmentStatus ? $state : ShipmentStatus::tryFrom((string) $state)) {
                                ShipmentStatus::Pending => 'gray',
                                ShipmentStatus::Packed => 'info',
                                ShipmentStatus::InTransit => 'primary',
                                ShipmentStatus::OutForDelivery => 'warning',
                                ShipmentStatus::Delivered => 'success',
                                ShipmentStatus::FailedDelivery, ShipmentStatus::Cancelled => 'danger',
                                ShipmentStatus::Returned => 'purple',
                                default => 'gray',
                            }),

                        TextEntry::make('tracking_code')
                            ->label('Consignment / Tracking #')
                            ->placeholder('Not assigned')
                            ->copyable(),

                        TextEntry::make('tracking_url')
                            ->label('Live Carrier Tracking')
                            ->state(fn (Shipment $record): ?string => $record->getTrackingUrl())
                            ->url(fn (Shipment $record): ?string => $record->getTrackingUrl(), shouldOpenInNewTab: true)
                            ->placeholder('No online tracking available')
                            ->color('primary'),

                        TextEntry::make('shipping_charge')
                            ->label('Shipping Fee')
                            ->formatStateUsing($currencyFormat)
                            ->weight('bold'),

                        TextEntry::make('weight_kg')
                            ->label('Weight')
                            ->formatStateUsing(fn ($state) => $state ? "{$state} kg" : '—')
                            ->placeholder('—'),

                        TextEntry::make('dispatchedBy.name')
                            ->label('Fulfillment Officer')
                            ->placeholder('System'),
                    ]),

                Section::make('Recipient & Delivery Destination')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 3,
                    ])
                    ->schema([
                        TextEntry::make('recipient_name')
                            ->label('Recipient Name')
                            ->weight('bold'),

                        TextEntry::make('recipient_phone')
                            ->label('Contact Phone')
                            ->copyable(),

                        TextEntry::make('shipping_address_line')
                            ->label('Delivery Street Address')
                            ->columnSpanFull(),

                        TextEntry::make('shipping_area')
                            ->label('Area / Thana')
                            ->placeholder('—'),

                        TextEntry::make('shipping_city')
                            ->label('City / District'),

                        TextEntry::make('shipping_postcode')
                            ->label('Postal Code')
                            ->placeholder('—'),

                        TextEntry::make('shipping_country')
                            ->label('Country'),
                    ]),

                Section::make('Milestone Timestamps')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 4,
                    ])
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('M j, Y H:i'),

                        TextEntry::make('packed_at')
                            ->label('Packed At')
                            ->dateTime('M j, Y H:i')
                            ->placeholder('Not packed yet'),

                        TextEntry::make('shipped_at')
                            ->label('Dispatched / Shipped At')
                            ->dateTime('M j, Y H:i')
                            ->placeholder('Awaiting dispatch'),

                        TextEntry::make('delivered_at')
                            ->label('Delivered At')
                            ->dateTime('M j, Y H:i')
                            ->placeholder('Pending delivery'),

                        TextEntry::make('returned_at')
                            ->label('Returned At')
                            ->dateTime('M j, Y H:i')
                            ->visible(fn (Shipment $record): bool => filled($record->returned_at)),

                        TextEntry::make('cancelled_at')
                            ->label('Cancelled At')
                            ->dateTime('M j, Y H:i')
                            ->visible(fn (Shipment $record): bool => filled($record->cancelled_at)),

                        TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->visible(fn (Shipment $record): bool => filled($record->notes)),
                    ]),
            ]);
    }
}
