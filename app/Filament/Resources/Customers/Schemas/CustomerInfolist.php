<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Customer Profile')
                    ->description(__('Identity, contact details, and optional linked store account.'))
                    ->icon(Heroicon::OutlinedUser)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
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
                            ->placeholder(__('No email registered')),

                        TextEntry::make('user.email')
                            ->label('Storefront Account')
                            ->placeholder(__('Guest / Not registered')),

                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean(),

                        TextEntry::make('created_at')
                            ->label('Customer Since')
                            ->dateTime('M j, Y H:i'),

                        TextEntry::make('notes')
                            ->label('Merchant Notes')
                            ->columnSpanFull()
                            ->visible(fn (Customer $record): bool => filled($record->notes)),
                    ]),

                Section::make('Commerce Summary')
                    ->description(__('Order volume and lifetime value for this customer.'))
                    ->icon(Heroicon::OutlinedChartBar)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
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
                    ->description(__('Preferred shipping and billing destinations.'))
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('default_shipping_address')
                            ->label('Default Shipping Address')
                            ->state(fn (Customer $record): ?string => $record->defaultShippingAddress?->formatted_address)
                            ->placeholder(__('No default shipping address saved')),

                        TextEntry::make('default_billing_address')
                            ->label('Default Billing Address')
                            ->state(fn (Customer $record): ?string => $record->defaultBillingAddress?->formatted_address)
                            ->placeholder(__('No default billing address saved')),
                    ]),
            ]);
    }
}
