<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Customer Name')
                    ->placeholder('e.g. Rahim Ahmed')
                    ->prefixIcon(Heroicon::OutlinedUser)
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Phone Number')
                    ->placeholder('017XXXXXXXX')
                    ->prefixIcon(Heroicon::OutlinedPhone)
                    ->required()
                    ->tel()
                    ->maxLength(30)
                    ->unique(Customer::class, 'phone', ignoreRecord: true),

                TextInput::make('email')
                    ->label('Email Address')
                    ->placeholder('customer@example.com')
                    ->prefixIcon(Heroicon::OutlinedEnvelope)
                    ->email()
                    ->nullable()
                    ->maxLength(255)
                    ->unique(Customer::class, 'email', ignoreRecord: true),

                Select::make('user_id')
                    ->label('Linked User Account')
                    ->placeholder('None (Guest / Walk-in)')
                    ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->unique(Customer::class, 'user_id', ignoreRecord: true),

                Textarea::make('notes')
                    ->label('Internal Merchant Notes')
                    ->placeholder('Special preferences, delivery instructions, VIP notes...')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Active Customer')
                    ->helperText('Active customers can place orders and receive notifications.')
                    ->default(true)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
