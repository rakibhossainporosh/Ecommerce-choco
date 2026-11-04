<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Setting Details')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextEntry::make('key')->weight('bold'),
                        TextEntry::make('label'),
                        TextEntry::make('group')->badge(),
                        TextEntry::make('value')->columnSpanFull(),
                        TextEntry::make('type')->badge(),
                        IconEntry::make('is_public')->boolean(),
                        IconEntry::make('is_system')->boolean(),
                    ]),
            ]);
    }
}
