<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

class SettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Setting Details')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('key')->weight('bold'),
                    TextEntry::make('label'),
                    TextEntry::make('group')->badge(),
                ]),
                Grid::make(1)->schema([
                    TextEntry::make('value'),
                ]),
                Grid::make(3)->schema([
                    TextEntry::make('type')->badge(),
                    IconEntry::make('is_public')->boolean(),
                    IconEntry::make('is_system')->boolean(),
                ]),
            ]),
        ]);
    }
}
