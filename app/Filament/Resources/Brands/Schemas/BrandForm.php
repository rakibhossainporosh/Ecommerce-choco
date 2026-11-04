<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Models\Brand;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Brand Information')
                    ->description(__('Core brand identity and search engine details.'))
                    ->icon(Heroicon::OutlinedBuildingStorefront)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Brand Name')
                            ->placeholder(__('e.g. Lindt, Ferrero, Godiva'))
                            ->prefixIcon(Heroicon::OutlinedBuildingStorefront)
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
                                        $fail(__('A brand with this name already exists.'));
                                    }
                                },
                            ]),

                        TextInput::make('slug')
                            ->label('URL Slug')
                            ->placeholder(__('e.g. lindt-chocolate'))
                            ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                            ->helperText(__('Used for SEO friendly brand links.'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder(__('Enter a detailed biography, history, or overview of this brand...'))
                            ->nullable()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Brand Logo & Media')
                    ->description(__('Visual branding asset for the storefront.'))
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Brand Logo')
                            ->image()
                            ->disk('public')
                            ->directory('brands/logos')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->helperText(__('PNG, JPG, or WebP up to 2MB.')),
                    ]),

                Section::make('Display & Status')
                    ->description(__('Storefront visibility and sorting sequence.'))
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->prefixIcon(Heroicon::OutlinedArrowsUpDown)
                            ->helperText(__('Lower values appear first (0 = top priority).'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->helperText(__('Disable to hide this brand from the storefront.'))
                            ->inline(false)
                            ->default(true)
                            ->required(),
                    ]),

                Section::make('Brand Insights')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ])
                    ->schema([
                        Placeholder::make('products_count')
                            ->label('Linked Products')
                            ->content(fn (?Brand $record): string => $record ? (string) $record->products()->count().' products' : '0 products'),

                        Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn (?Brand $record): string => $record?->created_at?->diffForHumans() ?? '—'),

                        Placeholder::make('updated_at')
                            ->label('Last Modified')
                            ->content(fn (?Brand $record): string => $record?->updated_at?->diffForHumans() ?? '—'),
                    ])
                    ->hidden(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
