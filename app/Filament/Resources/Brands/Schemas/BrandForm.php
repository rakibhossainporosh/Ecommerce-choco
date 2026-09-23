<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Models\Brand;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BrandForm
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
                    })
                    ->rules([
                        fn (?Brand $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            $trimmed = trim((string) $value);

                            $exists = Brand::query()
                                ->where('name', $trimmed)
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                ->exists();

                            if ($exists) {
                                $fail('A brand with this name already exists.');
                            }
                        },
                    ]),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(['alpha_dash']),

                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->disk('public')
                    ->directory('brands/logos')
                    ->maxSize(2048)
                    ->nullable(),

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
