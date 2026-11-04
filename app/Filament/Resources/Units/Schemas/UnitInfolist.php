<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Models\Unit;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UnitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Unit')
                    ->icon(Heroicon::OutlinedScale)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('code')->copyable(),
                        TextEntry::make('sort_order'),
                        IconEntry::make('is_active')->boolean(),
                        TextEntry::make('description')
                            ->placeholder(__('—'))
                            ->columnSpanFull(),
                        TextEntry::make('variants_count')
                            ->label('Linked variants')
                            ->state(fn (Unit $record): string => (string) ($record->variants_count ?? $record->variants()->count()).' variants'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ]),
            ]);
    }
}
