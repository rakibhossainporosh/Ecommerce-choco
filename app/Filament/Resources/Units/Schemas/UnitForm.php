<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Models\Unit;
use Closure;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Unit')
                    ->description(__('Name, code, status, and order for this selling unit.'))
                    ->icon(Heroicon::OutlinedScale)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->placeholder(__('e.g. Piece, Box, Kilogram'))
                            ->prefixIcon(Heroicon::OutlinedTag)
                            ->helperText(__('Shown on product forms and invoices.'))
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
                                        $fail(__('A unit with this name already exists.'));
                                    }
                                },
                            ]),

                        TextInput::make('code')
                            ->label('Code')
                            ->placeholder(__('e.g. pc, box, kg'))
                            ->prefixIcon(Heroicon::OutlinedHashtag)
                            ->helperText(__('Letters, numbers, dashes, underscores only.'))
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
                                        $fail(__('A unit with this code already exists.'));
                                    }
                                },
                            ]),

                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->prefixIcon(Heroicon::OutlinedArrowsUpDown)
                            ->helperText(__('Lower numbers appear first (0 = top).'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active status')
                            ->helperText(__('Turn off to hide this unit from product forms.'))
                            ->inline(false)
                            ->default(true)
                            ->required(),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder(__('Optional note for your team about when to use this unit...'))
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->columnSpanFull()
                            ->visible(fn (string $operation): bool => $operation === 'edit')
                            ->schema([
                                Placeholder::make('variants_count')
                                    ->label('Linked variants')
                                    ->content(fn (?Unit $record): string => $record
                                        ? (string) ($record->variants_count ?? $record->variants()->count()).' variants'
                                        : '0 variants'),

                                Placeholder::make('created_at')
                                    ->label('Created')
                                    ->content(fn (?Unit $record): string => $record?->created_at?->diffForHumans() ?? '—'),

                                Placeholder::make('updated_at')
                                    ->label('Last modified')
                                    ->content(fn (?Unit $record): string => $record?->updated_at?->diffForHumans() ?? '—'),
                            ]),
                    ]),
            ]);
    }
}
