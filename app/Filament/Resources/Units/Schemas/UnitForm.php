<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Models\Unit;
use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UnitForm
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
                    ->rules([
                        fn (?Unit $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            $trimmed = trim((string) $value);

                            $exists = Unit::query()
                                ->where('name', $trimmed)
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                ->exists();

                            if ($exists) {
                                $fail('A unit with this name already exists.');
                            }
                        },
                    ]),

                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(50)
                    ->live(onBlur: true)
                    ->dehydrateStateUsing(fn (?string $state) => $state !== null ? strtolower(trim($state)) : null)
                    ->rules([
                        'alpha_dash',
                        fn (?Unit $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            $normalized = strtolower(trim((string) $value));

                            $exists = Unit::withTrashed()
                                ->where('code', $normalized)
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                ->exists();

                            if ($exists) {
                                $fail('A unit with this code already exists.');
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
