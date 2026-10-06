<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
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
                Section::make('Order Summary')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 4,
                    ])
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('Order Number')
                            ->weight('bold'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (?OrderStatus $state): ?string => $state?->label()
                                ?? (is_string($state) ? OrderStatus::tryFrom($state)?->label() : null)
                                ?? $state?->value
                                ?? (string) $state
                            )
                            ->color(fn ($state): string => match ($state instanceof OrderStatus ? $state : OrderStatus::tryFrom((string) $state)) {
                                OrderStatus::Pending => 'warning',
                                OrderStatus::Confirmed => 'info',
                                OrderStatus::Processing => 'primary',
                                OrderStatus::Shipped => 'purple',
                                OrderStatus::Delivered => 'success',
                                OrderStatus::Cancelled => 'danger',
                                OrderStatus::Returned, OrderStatus::Refunded => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge()
                            ->formatStateUsing(fn (?PaymentStatus $state): ?string => $state?->label()
                                ?? (is_string($state) ? PaymentStatus::tryFrom($state)?->label() : null)
                                ?? $state?->value
                                ?? (string) $state
                            )
                            ->color(fn ($state): string => match ($state instanceof PaymentStatus ? $state : PaymentStatus::tryFrom((string) $state)) {
                                PaymentStatus::Paid => 'success',
                                PaymentStatus::Pending => 'warning',
                                PaymentStatus::Unpaid, PaymentStatus::Failed => 'danger',
                                PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded => 'gray',
                                default => 'gray',
                            }),

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

                Section::make('Customer Information')
                    ->columns([
                        'default' => 1,
                        'sm' => 3,
                    ])
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Customer Name'),

                        TextEntry::make('customer_phone')
                            ->label('Customer Phone'),

                        TextEntry::make('customer_email')
                            ->label('Customer Email')
                            ->placeholder('—'),

                        TextEntry::make('customer_note')
                            ->label('Customer Note')
                            ->visible(fn (Order $record): bool => filled($record->customer_note))
                            ->columnSpanFull(),
                    ]),

                Grid::make([
                    'default' => 1,
                    'md' => 2,
                ])
                    ->schema([
                        Section::make('Shipping Address')
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

                        Section::make('Billing Address')
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
                    ]),

                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('Ordered Products')
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label('Product'),

                                TextEntry::make('variant_name')
                                    ->label('Variant')
                                    ->placeholder('—'),

                                TextEntry::make('sku')
                                    ->label('SKU'),

                                TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->formatStateUsing($currencyFormat),

                                TextEntry::make('quantity')
                                    ->label('Quantity'),

                                TextEntry::make('discount_amount')
                                    ->label('Discount')
                                    ->formatStateUsing($currencyFormat),

                                TextEntry::make('line_total')
                                    ->label('Line Total')
                                    ->weight('bold')
                                    ->formatStateUsing($currencyFormat),
                            ])
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'md' => 4,
                                'lg' => 7,
                            ]),
                    ]),

                Section::make('Pricing')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 4,
                    ])
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->formatStateUsing($currencyFormat),

                        TextEntry::make('discount_amount')
                            ->label('Discount')
                            ->formatStateUsing($currencyFormat),

                        TextEntry::make('shipping_amount')
                            ->label('Shipping')
                            ->formatStateUsing($currencyFormat),

                        TextEntry::make('grand_total')
                            ->label('Grand Total')
                            ->weight('bold')
                            ->formatStateUsing($currencyFormat),
                    ]),

                Section::make('Status History')
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
                                    ->formatStateUsing(fn (?OrderStatus $state): ?string => $state?->label()
                                        ?? (is_string($state) ? OrderStatus::tryFrom($state)?->label() : null)
                                        ?? $state?->value
                                        ?? (string) $state
                                    )
                                    ->color(fn ($state): string => match ($state instanceof OrderStatus ? $state : OrderStatus::tryFrom((string) $state)) {
                                        OrderStatus::Pending => 'warning',
                                        OrderStatus::Confirmed => 'info',
                                        OrderStatus::Processing => 'primary',
                                        OrderStatus::Shipped => 'purple',
                                        OrderStatus::Delivered => 'success',
                                        OrderStatus::Cancelled => 'danger',
                                        OrderStatus::Returned, OrderStatus::Refunded => 'gray',
                                        default => 'gray',
                                    }),

                                TextEntry::make('to_status')
                                    ->label('To Status')
                                    ->badge()
                                    ->formatStateUsing(fn (?OrderStatus $state): ?string => $state?->label()
                                        ?? (is_string($state) ? OrderStatus::tryFrom($state)?->label() : null)
                                        ?? $state?->value
                                        ?? (string) $state
                                    )
                                    ->color(fn ($state): string => match ($state instanceof OrderStatus ? $state : OrderStatus::tryFrom((string) $state)) {
                                        OrderStatus::Pending => 'warning',
                                        OrderStatus::Confirmed => 'info',
                                        OrderStatus::Processing => 'primary',
                                        OrderStatus::Shipped => 'purple',
                                        OrderStatus::Delivered => 'success',
                                        OrderStatus::Cancelled => 'danger',
                                        OrderStatus::Returned, OrderStatus::Refunded => 'gray',
                                        default => 'gray',
                                    }),

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
            ]);
    }
}
