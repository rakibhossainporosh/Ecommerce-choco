<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, Set $set, Get $get): void {
                        if ($operation === 'create' || blank($get('slug'))) {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(['alpha_dash']),

                Select::make('parent_id')
                    ->label('Parent Category')
                    ->placeholder('None (Root Category)')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        ignoreRecord: true,
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->rules([
                        fn (?Category $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            if ($record && (int) $value === (int) $record->id) {
                                $fail('A category cannot be its own parent.');
                            }
                        },
                    ]),

                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),
            ]);
    }
}
