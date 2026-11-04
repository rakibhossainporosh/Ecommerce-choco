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
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('General Information')
                    ->description(__('Core product identity, catalog visibility, and organization.'))
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Product Name')
                            ->placeholder(__('e.g. Belgian Dark Chocolate Sea Salt'))
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
                            ->placeholder(__('e.g. belgian-dark-chocolate-sea-salt'))
                            ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                            ->helperText(__('Used for SEO friendly product links.'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Select::make('brand_id')
                            ->label('Brand')
                            ->placeholder(__('None (No Brand)'))
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
                                        $fail(__('Cannot assign an invalid, inactive, or soft-deleted brand.'));
                                    }
                                },
                            ]),

                        Select::make('categories')
                            ->label('Categories')
                            ->placeholder(__('Select categories...'))
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
                            ->helperText(__('At least one category is required when product is active.'))
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $categoryIds = is_array($value) ? array_map('intval', $value) : [];

                                    if (empty($categoryIds)) {
                                        if ((bool) $get('is_active')) {
                                            $fail(__('A product must have at least one category.'));
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

                        Toggle::make('is_active')
                            ->label('Active in Catalog')
                            ->helperText(__('Disable to hide product from the store.'))
                            ->inline(false)
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
                            ->helperText(__('Promote on homepage showcases.'))
                            ->inline(false)
                            ->default(false)
                            ->required()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if ((bool) $value && ! (bool) $get('is_active')) {
                                        $fail(__('An inactive product cannot be featured.'));
                                    }
                                },
                            ]),

                        Textarea::make('short_description')
                            ->label('Short Summary')
                            ->placeholder(__('Brief highlight of the product shown in listings and quick-views...'))
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Detailed Description')
                            ->placeholder(__('Comprehensive description, ingredients, dietary details, and tasting notes...'))
                            ->nullable()
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Initial Default Variant')
                    ->description(__('Every product must have at least one valid variant. Configure the initial default variant below.'))
                    ->icon(Heroicon::OutlinedSquare3Stack3d)
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextInput::make('variant_name')
                            ->label('Variant Name (Optional)')
                            ->placeholder(__('e.g. Standard, 100g Bar'))
                            ->prefixIcon(Heroicon::OutlinedTag)
                            ->nullable()
                            ->maxLength(255),

                        Select::make('unit_id')
                            ->label('Unit of Measure')
                            ->placeholder(__('Select unit...'))
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
                                        $fail(__('Cannot assign an invalid, inactive, or soft-deleted unit.'));
                                    }
                                },
                            ]),

                        TextInput::make('sku')
                            ->label('Variant SKU')
                            ->placeholder(__('e.g. SKU-BELGIAN-SALT-01'))
                            ->prefixIcon(Heroicon::OutlinedQrCode)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->unique(table: 'product_variants', column: 'sku')
                            ->rules(['alpha_dash']),

                        TextInput::make('barcode')
                            ->label('Barcode / UPC (Optional)')
                            ->placeholder(__('e.g. 7610400012345'))
                            ->prefixIcon(Heroicon::OutlinedIdentification)
                            ->nullable()
                            ->maxLength(255)
                            ->unique(table: 'product_variants', column: 'barcode'),

                        TextInput::make('unit_quantity')
                            ->label('Unit Quantity')
                            ->placeholder(__('1.000'))
                            ->prefixIcon(Heroicon::OutlinedHashtag)
                            ->numeric()
                            ->default(1)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minValue(0.001)
                            ->helperText(__('Quantity per packaging unit (e.g. 1 or 0.500).')),

                        TextInput::make('cost_price')
                            ->label('Cost Price')
                            ->placeholder(__('0.00'))
                            ->numeric()
                            ->prefix('৳')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minValue(0)
                            ->helperText(__('Purchase or production cost.')),

                        TextInput::make('selling_price')
                            ->label('Selling Price')
                            ->placeholder(__('0.00'))
                            ->numeric()
                            ->prefix('৳')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minValue(0)
                            ->helperText(__('Customer checkout price.')),

                        TextInput::make('compare_at_price')
                            ->label('Compare at Price')
                            ->placeholder(__('0.00'))
                            ->numeric()
                            ->prefix('৳')
                            ->nullable()
                            ->minValue(0)
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if ($value !== null && $value !== '') {
                                        $selling = $get('selling_price');
                                        if ($selling !== null && $selling !== '' && (float) $value < (float) $selling) {
                                            $fail(__('The compare at price must be greater than or equal to the selling price.'));
                                        }
                                    }
                                },
                            ])
                            ->helperText(__('Original price for discounts.')),

                        Section::make('Variant Dynamic Attributes')
                            ->description(__('Configure dynamic attributes for this initial variant.'))
                            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                            ->collapsible()
                            ->columnSpanFull()
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->components(fn () => static::getVariantAttributeComponents('variant_attributes')),
                    ]),

                Section::make('Product Attributes')
                    ->description(__('Configure dynamic attributes and specifications for this product.'))
                    ->icon(Heroicon::OutlinedAdjustmentsVertical)
                    ->collapsible()
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->components(fn () => static::getProductAttributeComponents()),

                Section::make('Search Engine Optimization (SEO)')
                    ->description(__('Configure metadata to improve search engine rankings.'))
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Meta Title (SEO)')
                            ->placeholder(__('Search engine title...'))
                            ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('meta_description')
                            ->label('Meta Description (SEO)')
                            ->placeholder(__('Search engine summary snippet...'))
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Product Insights')
                    ->description(__('Metadata and catalog statistics.'))
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        Placeholder::make('variants_count')
                            ->label('Total Variants')
                            ->content(fn (?Product $record): string => __('Variant count: :count', ['count' => $record ? $record->variants()->count() : 0])),

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
