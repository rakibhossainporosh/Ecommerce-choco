<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Enums\SettingType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Setting')
                    ->description(__('Key, type, value, and visibility for this configuration entry.'))
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('key')
                            ->label('Key')
                            ->required()
                            ->disabled(fn ($record) => $record?->is_system ?? false)
                            ->regex('/^[a-z0-9_]+$/')
                            ->unique(ignoreRecord: true)
                            ->prefixIcon(Heroicon::OutlinedHashtag)
                            ->helperText(__('Lowercase letters, numbers, and underscores only.')),

                        TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedTag)
                            ->helperText(__('Human-readable name shown in admin.')),

                        Select::make('type')
                            ->label('Type')
                            ->options(SettingType::class)
                            ->required()
                            ->disabled(fn ($record) => $record?->is_system ?? false)
                            ->live()
                            ->prefixIcon(Heroicon::OutlinedAdjustmentsHorizontal),

                        TextInput::make('group')
                            ->label('Group')
                            ->required()
                            ->default('general')
                            ->prefixIcon(Heroicon::OutlinedFolder)
                            ->datalist([
                                'general',
                                'footer',
                                'contact',
                                'payment',
                                'shipping',
                                'email',
                                'social',
                            ]),

                        TextInput::make('value')
                            ->label('Value')
                            ->columnSpanFull()
                            ->visible(fn ($get) => in_array($get('type') instanceof SettingType ? $get('type')->value : $get('type'), [SettingType::String->value, SettingType::Integer->value, SettingType::Float->value]))
                            ->numeric(fn ($get) => in_array($get('type') instanceof SettingType ? $get('type')->value : $get('type'), [SettingType::Integer->value, SettingType::Float->value])),

                        Textarea::make('value_json')
                            ->label('Value (JSON)')
                            ->rows(4)
                            ->columnSpanFull()
                            ->visible(fn ($get) => ($get('type') instanceof SettingType ? $get('type')->value : $get('type')) === SettingType::Json->value)
                            ->json()
                            ->formatStateUsing(fn ($record) => $record?->value)
                            ->dehydrateStateUsing(fn ($state) => $state),

                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->columnSpanFull()
                            ->schema([
                                Toggle::make('value_boolean')
                                    ->label('Value')
                                    ->inline(false)
                                    ->visible(fn ($get) => ($get('type') instanceof SettingType ? $get('type')->value : $get('type')) === SettingType::Boolean->value)
                                    ->formatStateUsing(fn ($record) => $record?->value === 'true')
                                    ->dehydrateStateUsing(fn ($state) => $state ? 'true' : 'false')
                                    ->afterStateHydrated(function (Toggle $component, $state, $record) {
                                        $component->state($record?->value === 'true');
                                    }),

                                Toggle::make('is_public')
                                    ->label('Public setting')
                                    ->helperText(__('Expose this setting to the public API / storefront.'))
                                    ->inline(false),

                                Toggle::make('is_system')
                                    ->label('System setting')
                                    ->disabled(fn ($record) => $record?->is_system ?? false)
                                    ->helperText(__('System settings cannot have their keys or types modified.'))
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }
}
