<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
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
                        TextInput::make('name')
                            ->label('Customer Name')
                            ->placeholder(__('e.g. Rahim Ahmed'))
                            ->prefixIcon(Heroicon::OutlinedUser)
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->placeholder(__('017XXXXXXXX'))
                            ->prefixIcon(Heroicon::OutlinedPhone)
                            ->required()
                            ->tel()
                            ->maxLength(30)
                            ->unique(Customer::class, 'phone', ignoreRecord: true),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->placeholder(__('customer@example.com'))
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            ->email()
                            ->nullable()
                            ->maxLength(255)
                            ->unique(Customer::class, 'email', ignoreRecord: true),

                        Select::make('user_id')
                            ->label('Linked User Account')
                            ->placeholder(__('None (Guest / Walk-in)'))
                            ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                            ->helperText(__('Optional. Link a registered storefront login to this customer profile.'))
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->unique(Customer::class, 'user_id', ignoreRecord: true),
                    ]),

                Section::make('Notes & Status')
                    ->description(__('Internal notes and storefront access status.'))
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Textarea::make('notes')
                            ->label('Internal Merchant Notes')
                            ->placeholder(__('Special preferences, delivery instructions, VIP notes...'))
                            ->nullable()
                            ->rows(4)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Active Customer')
                            ->helperText(__('Active customers can place orders and receive notifications.'))
                            ->inline(false)
                            ->default(true)
                            ->required(),
                    ]),

                Section::make('Customer Insights')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section fi-customer-insights-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        Placeholder::make('orders_count')
                            ->label('Orders')
                            ->content(function (?Customer $record): string {
                                $count = $record?->orders()->count() ?? 0;

                                return $count.' '.str('order')->plural($count);
                            }),

                        Placeholder::make('addresses_count')
                            ->label('Addresses')
                            ->content(function (?Customer $record): string {
                                $count = $record?->addresses()->count() ?? 0;

                                return $count.' '.str('address')->plural($count);
                            }),

                        Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn (?Customer $record): string => $record?->created_at?->diffForHumans() ?? '—'),

                        Placeholder::make('updated_at')
                            ->label('Last Modified')
                            ->content(fn (?Customer $record): string => $record?->updated_at?->diffForHumans() ?? '—'),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }
}
