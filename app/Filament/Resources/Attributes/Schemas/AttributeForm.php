<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Models\Attribute;
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
use Illuminate\Support\Str;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Group::make([
                    Section::make('Attribute Information')
                        ->description(__('Define the name, identifier, input type, and application scope.'))
                        ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Attribute Name')
                                ->placeholder(__('e.g. Cocoa Percentage, Flavor, Size'))
                                ->prefixIcon(Heroicon::OutlinedAdjustmentsHorizontal)
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
                                ->placeholder(__('e.g. cocoa-percentage'))
                                ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                                ->helperText(__('Used for system and API identification.'))
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->rules(['alpha_dash']),

                            Select::make('type')
                                ->label('Attribute Type')
                                ->options([
                                    Attribute::ATTRIBUTE_TYPE_TEXT => __('Text'),
                                    Attribute::ATTRIBUTE_TYPE_TEXTAREA => __('Textarea'),
                                    Attribute::ATTRIBUTE_TYPE_NUMBER => __('Number'),
                                    Attribute::ATTRIBUTE_TYPE_BOOLEAN => __('Boolean'),
                                    Attribute::ATTRIBUTE_TYPE_SELECT => __('Select (Single Choice)'),
                                    Attribute::ATTRIBUTE_TYPE_MULTISELECT => __('Multiselect (Multiple Choice)'),
                                ])
                                ->helperText(__('Select / Multiselect types support predefined option values.'))
                                ->required()
                                ->native(false),

                            Select::make('scope')
                                ->label('Scope')
                                ->options([
                                    Attribute::SCOPE_PRODUCT => __('Product (Global Specification)'),
                                    Attribute::SCOPE_VARIANT => __('Variant (Variant Option)'),
                                ])
                                ->helperText(__('Product: specification. Variant: creates stockable variants.'))
                                ->required()
                                ->native(false),

                            Textarea::make('description')
                                ->label('Description')
                                ->placeholder(__('Provide guidance on how this attribute is used...'))
                                ->nullable()
                                ->rows(4)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Behavior & Order')
                        ->description(__('Configure display sequence and assignment requirements.'))
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->schema([
                            TextInput::make('sort_order')
                                ->label('Sort Order')
                                ->prefixIcon(Heroicon::OutlinedArrowsUpDown)
                                ->helperText(__('Display priority (0 = top priority).'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->required(),

                            Toggle::make('is_required')
                                ->label('Mandatory Field')
                                ->helperText(__('When enabled, this attribute must be filled in forms.'))
                                ->default(false),

                            Toggle::make('is_active')
                                ->label('Active Status')
                                ->helperText(__('Disable to hide from product/variant assignment.'))
                                ->default(true),
                        ]),

                    Section::make('Attribute Insights')
                        ->icon(Heroicon::OutlinedInformationCircle)
                        ->schema([
                            Placeholder::make('values_count')
                                ->label('Predefined Values')
                                ->content(fn (?Attribute $record): string => $record ? (string) $record->values()->count().' values' : '0 values'),

                            Placeholder::make('assignments_count')
                                ->label('Assigned Items')
                                ->content(function (?Attribute $record): string {
                                    if (! $record) {
                                        return '0 items';
                                    }

                                    $count = $record->scope === Attribute::SCOPE_PRODUCT
                                        ? $record->productAttributeValues()->count()
                                        : $record->variantAttributeValues()->count();

                                    $unit = $record->scope === Attribute::SCOPE_PRODUCT ? 'products' : 'variants';

                                    return "{$count} {$unit}";
                                }),

                            Placeholder::make('created_at')
                                ->label('Created At')
                                ->content(fn (?Attribute $record): string => $record?->created_at?->diffForHumans() ?? '—'),

                            Placeholder::make('updated_at')
                                ->label('Last Modified')
                                ->content(fn (?Attribute $record): string => $record?->updated_at?->diffForHumans() ?? '—'),
                        ])
                        ->hidden(fn (string $operation): bool => $operation === 'create'),
                ])->columnSpan(['lg' => 1]),
            ]);
    }
}
