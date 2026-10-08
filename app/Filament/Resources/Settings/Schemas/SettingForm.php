<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Enums\SettingType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('key')
                    ->required()
                    ->disabled(fn ($record) => $record?->is_system ?? false)
                    ->regex('/^[a-z0-9_]+$/')
                    ->unique(ignoreRecord: true),

                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
            ]),

            Grid::make(2)->schema([
                Select::make('type')
                    ->options(SettingType::class)
                    ->required()
                    ->disabled(fn ($record) => $record?->is_system ?? false)
                    ->live(),

                TextInput::make('group')
                    ->required()
                    ->default('general')
                    ->datalist([
                        'general',
                        'payment',
                        'shipping',
                        'email',
                        'social',
                    ]),
            ]),

            Grid::make(1)->schema([
                TextInput::make('value')
                    ->visible(fn ($get) => in_array($get('type') instanceof SettingType ? $get('type')->value : $get('type'), [SettingType::String->value, SettingType::Integer->value, SettingType::Float->value]))
                    ->numeric(fn ($get) => in_array($get('type') instanceof SettingType ? $get('type')->value : $get('type'), [SettingType::Integer->value, SettingType::Float->value])),

                Toggle::make('value_boolean')
                    ->label('Value')
                    ->visible(fn ($get) => ($get('type') instanceof SettingType ? $get('type')->value : $get('type')) === SettingType::Boolean->value)
                    ->formatStateUsing(fn ($record) => $record?->value === 'true')
                    ->dehydrateStateUsing(fn ($state) => $state ? 'true' : 'false')
                    ->afterStateHydrated(function (Toggle $component, $state, $record) {
                        $component->state($record?->value === 'true');
                    }),

                Textarea::make('value_json')
                    ->label('Value (JSON)')
                    ->visible(fn ($get) => ($get('type') instanceof SettingType ? $get('type')->value : $get('type')) === SettingType::Json->value)
                    ->json()
                    ->formatStateUsing(fn ($record) => $record?->value)
                    ->dehydrateStateUsing(fn ($state) => $state),
            ]),

            Grid::make(2)->schema([
                Toggle::make('is_public')
                    ->label('Public Setting')
                    ->helperText('Should this setting be exposed to the public API/frontend?'),

                Toggle::make('is_system')
                    ->label('System Setting')
                    ->disabled(fn ($record) => $record?->is_system ?? false)
                    ->helperText('System settings cannot have their keys or types modified.'),
            ]),
        ]);
    }
}
