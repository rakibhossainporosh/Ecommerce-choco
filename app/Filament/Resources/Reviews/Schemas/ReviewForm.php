<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Review Details')
                    ->description(__('Product, customer, rating, and approval for this review.'))
                    ->icon(Heroicon::OutlinedStar)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->prefixIcon(Heroicon::OutlinedShoppingBag),

                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedUser)
                            ->rules([
                                fn ($get, $record) => Rule::unique('reviews', 'user_id')
                                    ->where('product_id', $get('product_id'))
                                    ->ignore($record?->id),
                            ])
                            ->validationMessages([
                                'unique' => __('This user has already reviewed this product.'),
                            ]),

                        Select::make('rating')
                            ->label('Rating')
                            ->options(fn (): array => [
                                1 => __('1 Star'),
                                2 => __('2 Stars'),
                                3 => __('3 Stars'),
                                4 => __('4 Stars'),
                                5 => __('5 Stars'),
                            ])
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedStar),

                        Toggle::make('is_approved')
                            ->label('Approved')
                            ->helperText(__('Only approved reviews show on the storefront.'))
                            ->inline(false)
                            ->default(false)
                            ->required(),

                        TextInput::make('title')
                            ->label('Title')
                            ->maxLength(255)
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ])
                            ->prefixIcon(Heroicon::OutlinedPencilSquare),

                        Textarea::make('body')
                            ->label('Review')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
