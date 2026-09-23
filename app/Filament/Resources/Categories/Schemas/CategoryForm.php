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
use Illuminate\Database\Eloquent\Builder;
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
                    })
                    ->rules([
                        fn (Get $get, ?Category $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                            $parentId = $get('parent_id') ? (int) $get('parent_id') : null;
                            $trimmed = trim((string) $value);

                            $exists = Category::query()
                                ->where('name', $trimmed)
                                ->when($parentId === null, fn ($q) => $q->whereNull('parent_id'), fn ($q) => $q->where('parent_id', $parentId))
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                ->exists();

                            if ($exists) {
                                $fail('A category with this name already exists under the selected parent.');
                            }
                        },
                    ]),

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
                        modifyQueryUsing: fn (Builder $query, ?Category $record): Builder => $record
                            ? $query->whereNotIn('id', array_merge([$record->id], $record->getDescendantIds()))
                            : $query,
                        ignoreRecord: true,
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->rules([
                        fn (Get $get, ?Category $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                            if (blank($value)) {
                                return;
                            }

                            $parentId = (int) $value;

                            if ($record && $parentId === (int) $record->id) {
                                $fail('A category cannot be its own parent.');

                                return;
                            }

                            if (Category::onlyTrashed()->where('id', $parentId)->exists()) {
                                $fail('Cannot assign a deleted category as parent.');

                                return;
                            }

                            if ($record && in_array($parentId, $record->getDescendantIds(), true)) {
                                $fail('Cannot assign this parent because it would create a circular category hierarchy.');

                                return;
                            }

                            $isActive = (bool) $get('is_active');
                            if ($isActive) {
                                $parent = Category::find($parentId);
                                if ($parent && (! $parent->is_active || $parent->hasInactiveAncestor())) {
                                    $fail('An active category cannot have an inactive parent.');

                                    return;
                                }
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
                    ->required()
                    ->rules([
                        fn (?Category $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            $willBeActive = (bool) $value;
                            if (! $willBeActive && $record && $record->hasActiveChildren()) {
                                $fail('Cannot deactivate this category because it has active child categories.');
                            }
                        },
                    ]),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),
            ]);
    }
}
