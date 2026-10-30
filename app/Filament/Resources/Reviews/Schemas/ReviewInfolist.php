<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

class ReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Review Information')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('product.name')->weight('bold'),
                    TextEntry::make('user.name')->weight('bold'),
                    TextEntry::make('rating'),
                ]),
                Grid::make(1)->schema([
                    TextEntry::make('title')->placeholder('No Title'),
                    TextEntry::make('body')->placeholder('No Review Body'),
                ]),
                Grid::make(2)->schema([
                    IconEntry::make('is_approved')->boolean(),
                    TextEntry::make('created_at')->dateTime(),
                ]),
            ]),
        ]);
    }
}
