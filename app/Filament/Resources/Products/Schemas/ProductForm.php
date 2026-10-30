<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Unit;
use Closure;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                // Left Column: Primary Content (2 columns on large screens)
                Group::make([
                    Section::make('General Information')
                        ->description('Core product identity and store presentation.')
                        ->icon(Heroicon::OutlinedShoppingBag)
                        ->columns(['default' => 1, 'md' => 2])
                        ->schema([
                            TextInput::make('name')
                                ->label('Product Name')
                                ->placeholder('e.g. Belgian Dark Chocolate Sea Salt')
                                ->prefixIcon(Heroicon::OutlinedShoppingBag)
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, ?string $state, Set $set, Get $get): void {
                                    if ($operation === 'create' || blank($get('slug'))) {
                                        $set('slug', Str::slug($state));
                                    }
                                }),

                            TextInput::make('slug')
                                ->label('URL Slug')
                                ->placeholder('e.g. belgian-dark-chocolate-sea-salt')
                                ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                                ->helperText('Used for SEO friendly product links.')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->rules(['alpha_dash']),

                            Textarea::make('short_description')
                                ->label('Short Summary')
                                ->placeholder('Brief highlight of the product shown in listings and quick-views...')
                                ->nullable()
                                ->rows(3)
                                ->columnSpanFull(),

                            Textarea::make('description')
                                ->label('Detailed Description')
                                ->placeholder('Comprehensive description, ingredients, dietary details, and tasting notes...')
                                ->nullable()
                                ->rows(5)
                                ->columnSpanFull(),
                        ]),

                    Section::make('Initial Default Variant')
                        ->description('Every product must have at least one valid variant. Configure the initial default variant below.')
                        ->icon(Heroicon::OutlinedSquare3Stack3d)
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->columns(['default' => 1, 'md' => 2])
                        ->schema([
                            TextInput::make('variant_name')
                                ->label('Variant Name (Optional)')
                                ->placeholder('e.g. Standard, 100g Bar')
                                ->prefixIcon(Heroicon::OutlinedTag)
                                ->nullable()
                                ->maxLength(255),

                            Select::make('unit_id')
                                ->label('Unit of Measure')
                                ->placeholder('Select unit...')
                                ->prefixIcon(Heroicon::OutlinedScale)
                                ->options(fn () => Unit::where('is_active', true)->pluck('name', 'id'))
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->searchable()
                                ->preload()
                                ->rules([
                                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                        if (blank($value)) {
                                            return;
                                        }

                                        $unit = Unit::withTrashed()->find($value);
                                        if (! $unit || $unit->trashed() || ! $unit->is_active) {
                                            $fail('Cannot assign an invalid, inactive, or soft-deleted unit.');
                                        }
                                    },
                                ]),

                            TextInput::make('sku')
                                ->label('Variant SKU')
                                ->placeholder('e.g. SKU-BELGIAN-SALT-01')
                                ->prefixIcon(Heroicon::OutlinedQrCode)
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->maxLength(255)
                                ->unique(table: 'product_variants', column: 'sku')
                                ->rules(['alpha_dash']),

                            TextInput::make('barcode')
                                ->label('Barcode / UPC (Optional)')
                                ->placeholder('e.g. 7610400012345')
                                ->prefixIcon(Heroicon::OutlinedIdentification)
                                ->nullable()
                                ->maxLength(255)
                                ->unique(table: 'product_variants', column: 'barcode'),

                            TextInput::make('unit_quantity')
                                ->label('Unit Quantity')
                                ->placeholder('1.000')
                                ->prefixIcon(Heroicon::OutlinedHashtag)
                                ->numeric()
                                ->default(1)
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->minValue(0.001)
                                ->helperText('Quantity per packaging unit (e.g. 1 or 0.500).')
                                ->columnSpanFull(),

                            Grid::make(['default' => 1, 'sm' => 3])
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('cost_price')
                                        ->label('Cost Price')
                                        ->placeholder('0.00')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required(fn (string $operation): bool => $operation === 'create')
                                        ->minValue(0)
                                        ->helperText('Purchase or production cost.'),

                                    TextInput::make('selling_price')
                                        ->label('Selling Price')
                                        ->placeholder('0.00')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required(fn (string $operation): bool => $operation === 'create')
                                        ->minValue(0)
                                        ->helperText('Customer checkout price.'),

                                    TextInput::make('compare_at_price')
                                        ->label('Compare at Price')
                                        ->placeholder('0.00')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->nullable()
                                        ->minValue(0)
                                        ->rules([
                                            fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                                if ($value !== null && $value !== '') {
                                                    $selling = $get('selling_price');
                                                    if ($selling !== null && $selling !== '' && (float) $value < (float) $selling) {
                                                        $fail('The compare at price must be greater than or equal to the selling price.');
                                                    }
                                                }
                                            },
                                        ])
                                        ->helperText('Original price for discounts.'),
                                ]),

                            Section::make('Variant Dynamic Attributes')
                                ->description('Configure dynamic attributes for this initial variant.')
                                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                                ->collapsible()
                                ->columnSpanFull()
                                ->columns(['default' => 1, 'md' => 2])
                                ->components(fn () => static::getVariantAttributeComponents('variant_attributes')),
                        ]),

                    Section::make('Product Attributes')
                        ->description('Configure dynamic attributes and specifications for this product.')
                        ->icon(Heroicon::OutlinedAdjustmentsVertical)
                        ->collapsible()
                        ->columns(['default' => 1, 'md' => 2])
                        ->components(fn () => static::getProductAttributeComponents()),

                    Section::make('Search Engine Optimization (SEO)')
                        ->description('Configure metadata to improve search engine rankings.')
                        ->icon(Heroicon::OutlinedMagnifyingGlass)
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('meta_title')
                                ->label('Meta Title (SEO)')
                                ->placeholder('Search engine title...')
                                ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                                ->nullable()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Textarea::make('meta_description')
                                ->label('Meta Description (SEO)')
                                ->placeholder('Search engine summary snippet...')
                                ->nullable()
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(['lg' => 2]),

                // Right Column: Status, Organization & Insights (1 column on large screens)
                Group::make([
                    Section::make('Status & Visibility')
                        ->description('Storefront catalog availability.')
                        ->icon(Heroicon::OutlinedEye)
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Active in Catalog')
                                ->helperText('Disable to hide product from the store.')
                                ->default(true)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (bool $state, Set $set): void {
                                    if (! $state) {
                                        $set('is_featured', false);
                                    }
                                }),

                            Toggle::make('is_featured')
                                ->label('Featured Product')
                                ->helperText('Promote on homepage showcases.')
                                ->default(false)
                                ->required()
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        if ((bool) $value && ! (bool) $get('is_active')) {
                                            $fail('An inactive product cannot be featured.');
                                        }
                                    },
                                ]),
                        ]),

                    Section::make('Organization')
                        ->description('Brand and category categorization.')
                        ->icon(Heroicon::OutlinedRectangleStack)
                        ->schema([
                            Select::make('brand_id')
                                ->label('Brand')
                                ->placeholder('None (No Brand)')
                                ->prefixIcon(Heroicon::OutlinedBuildingStorefront)
                                ->relationship(
                                    name: 'brand',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true),
                                )
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->rules([
                                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                        if (blank($value)) {
                                            return;
                                        }

                                        $brand = Brand::withTrashed()->find($value);

                                        if (! $brand || $brand->trashed() || ! $brand->is_active) {
                                            $fail('Cannot assign an invalid, inactive, or soft-deleted brand.');
                                        }
                                    },
                                ]),

                            Select::make('categories')
                                ->label('Categories')
                                ->placeholder('Select categories...')
                                ->prefixIcon(Heroicon::OutlinedTag)
                                ->relationship(
                                    name: 'categories',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true),
                                )
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => (bool) $get('is_active'))
                                ->helperText('At least one category is required when product is active.')
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        $categoryIds = is_array($value) ? array_map('intval', $value) : [];

                                        if (empty($categoryIds)) {
                                            if ((bool) $get('is_active')) {
                                                $fail('A product must have at least one category.');
                                            }

                                            return;
                                        }

                                        try {
                                            Product::validateCategoryAssignment($categoryIds);
                                        } catch (ValidationException $e) {
                                            $messages = $e->errors()['categories'] ?? ['Invalid category assignment.'];
                                            $fail($messages[0]);
                                        }
                                    },
                                ]),
                        ]),

                    Section::make('Product Insights')
                        ->description('Metadata and catalog statistics.')
                        ->icon(Heroicon::OutlinedInformationCircle)
                        ->schema([
                            Placeholder::make('variants_count')
                                ->label('Total Variants')
                                ->content(fn (?Product $record): string => $record ? (string) $record->variants()->count().' variants' : '0 variants'),

                            Placeholder::make('default_variant_sku')
                                ->label('Default Variant SKU')
                                ->content(fn (?Product $record): string => $record?->defaultVariant?->sku ?? 'None'),

                            Placeholder::make('created_at')
                                ->label('Created At')
                                ->content(fn (?Product $record): string => $record?->created_at?->diffForHumans() ?? '—'),

                            Placeholder::make('updated_at')
                                ->label('Last Modified')
                                ->content(fn (?Product $record): string => $record?->updated_at?->diffForHumans() ?? '—'),
                        ])
                        ->hidden(fn (string $operation): bool => $operation === 'create'),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    /**
     * @return array<Component>
     */
    public static function getProductAttributeComponents(string $statePrefix = 'product_attributes'): array
    {
        $attributes = Attribute::query()
            ->where('scope', Attribute::SCOPE_PRODUCT)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $components = [];

        foreach ($attributes as $attribute) {
            $components[] = static::createAttributeField($attribute, $statePrefix);
        }

        return $components;
    }

    /**
     * @return array<Component>
     */
    public static function getVariantAttributeComponents(string $statePrefix = 'variant_attributes'): array
    {
        $attributes = Attribute::query()
            ->where('scope', Attribute::SCOPE_VARIANT)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $components = [];

        foreach ($attributes as $attribute) {
            $components[] = static::createAttributeField($attribute, $statePrefix);
        }

        return $components;
    }

    public static function createAttributeField(Attribute $attribute, string $statePrefix): mixed
    {
        $statePath = "{$statePrefix}.{$attribute->id}";

        $field = match ($attribute->type) {
            Attribute::ATTRIBUTE_TYPE_TEXT => TextInput::make($statePath)
                ->label($attribute->name)
                ->maxLength(255),

            Attribute::ATTRIBUTE_TYPE_TEXTAREA => Textarea::make($statePath)
                ->label($attribute->name)
                ->rows(3)
                ->columnSpanFull(),

            Attribute::ATTRIBUTE_TYPE_NUMBER => TextInput::make($statePath)
                ->label($attribute->name)
                ->numeric(),

            Attribute::ATTRIBUTE_TYPE_BOOLEAN => Toggle::make($statePath)
                ->label($attribute->name)
                ->default(false),

            Attribute::ATTRIBUTE_TYPE_SELECT => Select::make($statePath)
                ->label($attribute->name)
                ->options(fn () => $attribute->values()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->native(false),

            Attribute::ATTRIBUTE_TYPE_MULTISELECT => Select::make($statePath)
                ->label($attribute->name)
                ->multiple()
                ->options(fn () => $attribute->values()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->native(false),
        };

        if (filled($attribute->description)) {
            $field->helperText($attribute->description);
        }

        if ($attribute->is_required) {
            $field->required();
        }

        return $field;
    }
}
