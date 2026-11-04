<?php

namespace App\Filament\Resources\Couriers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CourierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Courier Details')
                    ->description(__('Partner identity, API endpoint, and activation status.'))
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedBuildingOffice2),

                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedHashtag),

                        TextInput::make('api_base_url')
                            ->label('API Base URL')
                            ->url()
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedGlobeAlt),

                        Toggle::make('is_active')
                            ->label('Is active')
                            ->helperText(__('Inactive couriers cannot be assigned to new consignments.'))
                            ->inline(false)
                            ->default(true),
                    ]),

                Section::make('API Credentials')
                    ->description(__('Enter the API key and secret from your courier partner dashboard. Existing secrets stay hidden for security.'))
                    ->icon(Heroicon::OutlinedKey)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('credentials.api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedKey)
                            ->placeholder(__('Leave blank to keep the current key'))
                            ->helperText(__('Usually labeled API Key, Client ID, or Access Key in the courier portal.')),

                        TextInput::make('credentials.secret_key')
                            ->label('API Secret')
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedLockClosed)
                            ->placeholder(__('Leave blank to keep the current secret'))
                            ->helperText(__('Usually labeled API Secret, Client Secret, or Secret Key in the courier portal.')),
                    ]),
            ]);
    }
}
