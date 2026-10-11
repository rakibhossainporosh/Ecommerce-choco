<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $statusColor = fn ($state): string => match ($state instanceof OrderStatus ? $state : OrderStatus::tryFrom((string) $state)) {
            OrderStatus::Pending => 'warning',
            OrderStatus::Confirmed => 'info',
            OrderStatus::Processing => 'primary',
            OrderStatus::Shipped => 'purple',
            OrderStatus::Delivered => 'success',
            OrderStatus::Cancelled => 'danger',
            OrderStatus::Returned, OrderStatus::Refunded => 'gray',
            default => 'gray',
        };

        $statusLabel = fn (?OrderStatus $state): ?string => $state?->label()
            ?? (is_string($state) ? OrderStatus::tryFrom($state)?->label() : null)
            ?? $state?->value
            ?? (string) $state;

        $paymentStatusColor = fn ($state): string => match ($state instanceof PaymentStatus ? $state : PaymentStatus::tryFrom((string) $state)) {
            PaymentStatus::Paid => 'success',
            PaymentStatus::Pending => 'warning',
            PaymentStatus::Unpaid, PaymentStatus::Failed => 'danger',
            PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded => 'gray',
            default => 'gray',
        };

        $paymentStatusLabel = fn (?PaymentStatus $state): ?string => $state?->label()
            ?? (is_string($state) ? PaymentStatus::tryFrom($state)?->label() : null)
            ?? $state?->value
            ?? (string) $state;

        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'xl' => 3,
                ])
                    ->schema([
                        // Main Left Column (2/3 width on wide screens)
                        Group::make([
                            // Section: Order Items Table
                            Section::make('Ordered Products')
                                ->icon(Heroicon::OutlinedShoppingBag)
                                ->schema([
                                    View::make('filament.infolists.order-items-table'),
                                ]),

                            // Section: Shipment & Fulfillment Progress
                            Section::make('Shipment & Fulfillment Status')
                                ->icon(Heroicon::OutlinedTruck)
                                ->columns([
                                    'default' => 1,
                                    'sm' => 3,
                                ])
                                ->schema([
                                    TextEntry::make('fulfillment_status')
                                        ->label('Delivery Progress')
                                        ->state(fn (Order $record): string => $record->latestShipment?->status?->label() ?? 'Awaiting Fulfillment')
                                        ->badge()
                                        ->color(fn (Order $record): string => $record->latestShipment ? $record->latestShipment->status->badgeColor() : 'gray'),

                                    TextEntry::make('courier_partner')
                                        ->label('Carrier Partner')
                                        ->state(fn (Order $record): string => $record->latestShipment?->provider?->label() ?? 'Not Dispatched')
                                        ->badge(),

                                    TextEntry::make('tracking_number')
                                        ->label('Consignment Tracking')
                                        ->state(fn (Order $record): string => $record->latestShipment?->tracking_code ?? 'No tracking code')
                                        ->url(fn (Order $record): ?string => $record->latestShipment?->getTrackingUrl(), shouldOpenInNewTab: true)
                                        ->color(fn (Order $record): ?string => $record->latestShipment?->tracking_code ? 'primary' : null)
                                        ->copyable(fn (Order $record): bool => filled($record->latestShipment?->tracking_code)),
                                ]),

                            // Section: Status History & Audit Trail
                            Section::make('Status History & Audit Trail')
                                ->icon(Heroicon::OutlinedClock)
                                ->collapsible()
                                ->schema([
                                    RepeatableEntry::make('statusHistories')
                                        ->label('Audit Trail')
                                        ->placeholder('No status transitions recorded.')
                                        ->schema([
                                            TextEntry::make('created_at')
                                                ->label('Date & Time')
                                                ->dateTime(),

                                            TextEntry::make('from_status')
                                                ->label('From Status')
                                                ->badge()
                                                ->formatStateUsing($statusLabel)
                                                ->color($statusColor),

                                            TextEntry::make('to_status')
                                                ->label('To Status')
                                                ->badge()
                                                ->formatStateUsing($statusLabel)
                                                ->color($statusColor),

                                            TextEntry::make('changedBy.name')
                                                ->label('Changed By')
                                                ->placeholder('System'),

                                            TextEntry::make('reason')
                                                ->label('Reason')
                                                ->placeholder('—'),
                                        ])
                                        ->columns([
                                            'default' => 1,
                                            'sm' => 2,
                                            'md' => 5,
                                        ]),
                                ]),
                        ])
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),

                        // Right Sidebar Column (1/3 width on wide screens)
                        Group::make([
                            // Section: Order Summary (Clean single column so values never wrap)
                            Section::make('Order Summary')
                                ->icon(Heroicon::OutlinedInformationCircle)
                                ->schema([
                                    TextEntry::make('order_number')
                                        ->label('Order Number')
                                        ->weight('bold')
                                        ->copyable(),

                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->formatStateUsing($statusLabel)
                                        ->color($statusColor),

                                    TextEntry::make('payment_status')
                                        ->label('Payment Status')
                                        ->badge()
                                        ->formatStateUsing($paymentStatusLabel)
                                        ->color($paymentStatusColor),

                                    TextEntry::make('payment_method')
                                        ->label('Payment Method')
                                        ->formatStateUsing(fn (?PaymentMethod $state): ?string => $state?->label()
                                            ?? (is_string($state) ? PaymentMethod::tryFrom($state)?->label() : null)
                                            ?? (string) $state
                                        ),

                                    TextEntry::make('currency')
                                        ->label('Currency'),

                                    TextEntry::make('placed_at')
                                        ->label('Placed At')
                                        ->dateTime()
                                        ->placeholder('—'),

                                    TextEntry::make('created_at')
                                        ->label('Created At')
                                        ->dateTime(),

                                    TextEntry::make('cancellation_reason')
                                        ->label('Cancellation Reason')
                                        ->color('danger')
                                        ->visible(fn (Order $record): bool => $record->isCancelled() && filled($record->cancellation_reason)),
                                ]),

                            // Section: Customer Information
                            Section::make('Customer Information')
                                ->icon(Heroicon::OutlinedUser)
                                ->schema([
                                    TextEntry::make('customer_name')
                                        ->label('Customer Name')
                                        ->weight('bold'),

                                    TextEntry::make('customer_phone')
                                        ->label('Customer Phone')
                                        ->copyable(),

                                    TextEntry::make('customer_email')
                                        ->label('Customer Email')
                                        ->copyable()
                                        ->placeholder('—'),

                                    TextEntry::make('customer_note')
                                        ->label('Customer Note')
                                        ->visible(fn (Order $record): bool => filled($record->customer_note)),
                                ]),

                            // Section: Shipping Address
                            Section::make('Shipping Address')
                                ->icon(Heroicon::OutlinedMapPin)
                                ->schema([
                                    TextEntry::make('shipping_address_line')
                                        ->label('Address Line'),

                                    TextEntry::make('shipping_area')
                                        ->label('Area')
                                        ->placeholder('—'),

                                    TextEntry::make('shipping_city')
                                        ->label('City'),

                                    TextEntry::make('shipping_postcode')
                                        ->label('Postcode')
                                        ->placeholder('—'),

                                    TextEntry::make('shipping_country')
                                        ->label('Country'),
                                ]),

                            // Section: Billing Address
                            Section::make('Billing Address')
                                ->icon(Heroicon::OutlinedCreditCard)
                                ->schema([
                                    TextEntry::make('billing_address_display')
                                        ->label('Billing Address')
                                        ->state(fn (Order $record): string => 'Same as shipping address')
                                        ->visible(fn (Order $record): bool => (bool) $record->billing_same_as_shipping),

                                    TextEntry::make('billing_address_line')
                                        ->label('Address Line')
                                        ->visible(fn (Order $record): bool => ! $record->billing_same_as_shipping),

                                    TextEntry::make('billing_area')
                                        ->label('Area')
                                        ->placeholder('—')
                                        ->visible(fn (Order $record): bool => ! $record->billing_same_as_shipping),

                                    TextEntry::make('billing_city')
                                        ->label('City')
                                        ->visible(fn (Order $record): bool => ! $record->billing_same_as_shipping),

                                    TextEntry::make('billing_postcode')
                                        ->label('Postcode')
                                        ->placeholder('—')
                                        ->visible(fn (Order $record): bool => ! $record->billing_same_as_shipping),

                                    TextEntry::make('billing_country')
                                        ->label('Country')
                                        ->visible(fn (Order $record): bool => ! $record->billing_same_as_shipping),
                                ]),
                        ])
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 1,
                            ]),
                    ]),
            ]);
    }
}
