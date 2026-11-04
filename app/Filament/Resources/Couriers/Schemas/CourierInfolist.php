<?php

namespace App\Filament\Resources\Couriers\Schemas;

use App\Models\Courier;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CourierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Courier Details')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('code')->copyable(),
                        TextEntry::make('api_base_url')
                            ->label('API Base URL')
                            ->placeholder(__('—'))
                            ->copyable(),
                        IconEntry::make('is_active')->boolean(),
                    ]),

                Section::make('API Credentials')
                    ->icon(Heroicon::OutlinedKey)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('api_key_status')
                            ->label('API Key')
                            ->state(fn (Courier $record): string => filled(data_get($record->credentials, 'api_key')) ? __('Configured') : __('Not set'))
                            ->badge()
                            ->color(fn (string $state): string => $state === __('Configured') ? 'success' : 'gray'),
                        TextEntry::make('secret_key_status')
                            ->label('API Secret')
                            ->state(fn (Courier $record): string => filled(data_get($record->credentials, 'secret_key')) ? __('Configured') : __('Not set'))
                            ->badge()
                            ->color(fn (string $state): string => $state === __('Configured') ? 'success' : 'gray'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('shipments_count')
                            ->label('Shipments')
                            ->state(fn (Courier $record): string => (string) $record->shipments()->count()),
                    ]),
            ]);
    }
}
