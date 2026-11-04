<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('General Information')
                    ->icon(Heroicon::OutlinedTag)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('slug')->copyable(),
                        TextEntry::make('description')
                            ->placeholder(__('—'))
                            ->columnSpanFull(),
                    ]),

                Section::make('Hierarchy & Placement')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ])
                    ->schema([
                        TextEntry::make('parent.name')
                            ->label('Parent Category')
                            ->placeholder(__('None (Root Category)')),
                        TextEntry::make('sort_order'),
                        IconEntry::make('is_active')->boolean(),
                    ]),

                Section::make('Category Insights')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextEntry::make('products_count')
                            ->label('Linked Products')
                            ->state(fn (Category $record): string => $record->products()->count().' products'),
                        TextEntry::make('children_count')
                            ->label('Subcategories')
                            ->state(fn (Category $record): string => $record->children()->count().' subcategories'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ]),
            ]);
    }
}
