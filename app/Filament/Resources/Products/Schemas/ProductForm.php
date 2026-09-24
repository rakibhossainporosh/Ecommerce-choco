<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Unit;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Product Name')
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

                Select::make('brand_id')
                    ->label('Brand')
                    ->placeholder('None (No Brand)')
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
                    ->relationship(
                        name: 'categories',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => (bool) $get('is_active'))
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

                Textarea::make('short_description')
                    ->label('Short Description')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Full Description')
                    ->nullable()
                    ->rows(5)
                    ->columnSpanFull(),

                TextInput::make('meta_title')
                    ->label('Meta Title (SEO)')
                    ->nullable()
                    ->maxLength(255),

                Textarea::make('meta_description')
                    ->label('Meta Description (SEO)')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (bool $state, Set $set): void {
                        if (! $state) {
                            $set('is_featured', false);
                        }
                    }),

                Toggle::make('is_featured')
                    ->label('Featured')
                    ->default(false)
                    ->required()
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if ((bool) $value && ! (bool) $get('is_active')) {
                                $fail('An inactive product cannot be featured.');
                            }
                        },
                    ]),

                Section::make('Product Attributes')
                    ->description('Configure dynamic attributes and specifications for this product.')
                    ->components(fn () => static::getProductAttributeComponents()),

                Section::make('Initial Default Variant')
                    ->description('Every product must have at least one valid variant. Configure the initial default variant below.')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->components([
                        TextInput::make('variant_name')
                            ->label('Variant Name (Optional)')
                            ->placeholder('e.g. Standard, 100g Bar')
                            ->nullable()
                            ->maxLength(255),

                        Select::make('unit_id')
                            ->label('Unit')
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
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->unique(table: 'product_variants', column: 'sku')
                            ->rules(['alpha_dash']),

                        TextInput::make('barcode')
                            ->label('Barcode (Optional)')
                            ->nullable()
                            ->maxLength(255)
                            ->unique(table: 'product_variants', column: 'barcode'),

                        TextInput::make('cost_price')
                            ->label('Cost Price (BDT)')
                            ->numeric()
                            ->prefix('BDT')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minValue(0),

                        TextInput::make('selling_price')
                            ->label('Selling Price (BDT)')
                            ->numeric()
                            ->prefix('BDT')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minValue(0),

                        TextInput::make('compare_at_price')
                            ->label('Compare at Price (BDT)')
                            ->numeric()
                            ->prefix('BDT')
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
                            ]),

                        TextInput::make('unit_quantity')
                            ->label('Unit Quantity')
                            ->numeric()
                            ->default(1)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minValue(0.001),

                        Section::make('Variant Attributes')
                            ->description('Configure dynamic attributes for this initial variant.')
                            ->components(fn () => static::getVariantAttributeComponents('variant_attributes')),
                    ]),
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
