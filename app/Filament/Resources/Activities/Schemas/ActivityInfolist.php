<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('log_name')->badge(),
                        TextEntry::make('description'),
                        TextEntry::make('subject_type')
                            ->label('Subject')
                            ->formatStateUsing(fn ($state, $record) => class_basename($state).' #'.$record->subject_id),
                        TextEntry::make('causer.name')
                            ->label('Causer'),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
                Section::make('Properties')
                    ->schema([
                        KeyValueEntry::make('properties.attributes')
                            ->label('New Attributes'),
                        KeyValueEntry::make('properties.old')
                            ->label('Old Attributes'),
                    ])
                    ->visible(fn ($record) => $record->properties?->count() > 0),
            ]);
    }
}
