<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Review Information')
                    ->icon(Heroicon::OutlinedStar)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextEntry::make('product.name')->weight('bold'),
                        TextEntry::make('user.name')->weight('bold'),
                        TextEntry::make('rating'),
                        TextEntry::make('title')
                            ->placeholder(__('No Title'))
                            ->columnSpanFull(),
                        TextEntry::make('body')
                            ->placeholder(__('No Review Body'))
                            ->columnSpanFull(),
                        IconEntry::make('is_approved')->boolean(),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
            ]);
    }
}
