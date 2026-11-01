<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Review Details')->schema([
                Grid::make(2)->schema([
                    Select::make('product_id')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->rules([
                            fn ($get, $record) => Rule::unique('reviews', 'user_id')
                                ->where('product_id', $get('product_id'))
                                ->ignore($record?->id),
                        ])
                        ->validationMessages([
                            'unique' => __('This user has already reviewed this product.'),
                        ]),
                    Select::make('rating')
                        ->options(fn (): array => [
                            1 => __('1 Star'),
                            2 => __('2 Stars'),
                            3 => __('3 Stars'),
                            4 => __('4 Stars'),
                            5 => __('5 Stars'),
                        ])
                        ->required(),
                    TextInput::make('title')
                        ->maxLength(255),
                    Textarea::make('body')
                        ->columnSpanFull(),
                    Toggle::make('is_approved')
                        ->default(false)
                        ->required(),
                ]),
            ]),
        ]);
    }
}
