<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
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
                Section::make('Customer Profile')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'md' => 3,
                    ])
                    ->schema([
                        TextEntry::make('name')
                            ->label('Full Name')
                            ->weight('bold'),

                        TextEntry::make('phone')
                            ->label('Phone Number')
                            ->copyable(),

                        TextEntry::make('email')
                            ->label('Email Address')
                            ->placeholder('No email registered'),

                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean(),

                        TextEntry::make('user.email')
                            ->label('Storefront Account')
                            ->placeholder('Guest / Not registered'),

                        TextEntry::make('created_at')
                            ->label('Customer Since')
                            ->dateTime('M j, Y H:i'),

                        TextEntry::make('notes')
                            ->label('Merchant Notes')
                            ->columnSpanFull()
                            ->visible(fn (Customer $record): bool => filled($record->notes)),
                    ]),

                Section::make('Commerce Summary')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                    ])
                    ->schema([
                        TextEntry::make('orders_count')
                            ->label('Total Orders Placed')
                            ->state(fn (Customer $record): int => $record->orders_count)
                            ->badge()
                            ->color('info'),

                        TextEntry::make('total_spent')
                            ->label('Lifetime Spend (LTV)')
                            ->state(fn (Customer $record): float => $record->total_spent)
                            ->formatStateUsing($currencyFormat)
                            ->weight('bold'),
                    ]),

                Section::make('Default Addresses')
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                    ])
                    ->schema([
                        TextEntry::make('default_shipping_address')
                            ->label('Default Shipping Address')
                            ->state(fn (Customer $record): ?string => $record->defaultShippingAddress?->formatted_address)
                            ->placeholder('No default shipping address saved'),

                        TextEntry::make('default_billing_address')
                            ->label('Default Billing Address')
                            ->state(fn (Customer $record): ?string => $record->defaultBillingAddress?->formatted_address)
                            ->placeholder('No default billing address saved'),
                    ]),
            ]);
    }
}
