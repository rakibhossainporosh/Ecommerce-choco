<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Closure;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Group::make([
                    Section::make('General Information')
                        ->description('Basic identity and search engine details for this category.')
                        ->icon(Heroicon::OutlinedTag)
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Category Name')
                                ->placeholder('e.g. Dark Chocolates, Truffles')
                                ->prefixIcon(Heroicon::OutlinedTag)
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
                                ->label('URL Slug')
                                ->placeholder('e.g. dark-chocolates')
                                ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                                ->helperText('Used for SEO friendly URL links.')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->rules(['alpha_dash']),

                            Textarea::make('description')
                                ->label('Description')
                                ->placeholder('Enter a clear description of this category for customers and SEO...')
                                ->nullable()
                                ->rows(4)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Hierarchy & Placement')
                        ->description('Category tree level and display sequence.')
                        ->icon(Heroicon::OutlinedRectangleStack)
                        ->schema([
                            Select::make('parent_id')
                                ->label('Parent Category')
                                ->placeholder('None (Root Category)')
                                ->prefixIcon(Heroicon::OutlinedFolder)
                                ->helperText('Leave empty to create a top-level root category.')
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

                            TextInput::make('sort_order')
                                ->label('Sort Order')
                                ->prefixIcon(Heroicon::OutlinedArrowsUpDown)
                                ->helperText('Lower numbers appear first (0 = top priority).')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->required(),
                        ]),

                    Section::make('Visibility & Status')
                        ->description('Storefront catalog availability.')
                        ->icon(Heroicon::OutlinedEye)
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Active Status')
                                ->helperText('Disable to hide category from the store.')
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
                        ]),

                    Section::make('Category Insights')
                        ->icon(Heroicon::OutlinedInformationCircle)
                        ->schema([
                            Placeholder::make('products_count')
                                ->label('Linked Products')
                                ->content(fn (?Category $record): string => $record ? (string) $record->products()->count().' products' : '0 products'),

                            Placeholder::make('children_count')
                                ->label('Subcategories')
                                ->content(fn (?Category $record): string => $record ? (string) $record->children()->count().' subcategories' : '0 subcategories'),

                            Placeholder::make('created_at')
                                ->label('Created At')
                                ->content(fn (?Category $record): string => $record?->created_at?->diffForHumans() ?? '—'),

                            Placeholder::make('updated_at')
                                ->label('Last Modified')
                                ->content(fn (?Category $record): string => $record?->updated_at?->diffForHumans() ?? '—'),
                        ])
                        ->hidden(fn (string $operation): bool => $operation === 'create'),
                ])->columnSpan(['lg' => 1]),
            ]);
    }
}
