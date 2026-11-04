<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Models\Attribute;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AttributeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Attribute Information')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('slug')->copyable(),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                Attribute::ATTRIBUTE_TYPE_TEXT => __('Text'),
                                Attribute::ATTRIBUTE_TYPE_TEXTAREA => __('Textarea'),
                                Attribute::ATTRIBUTE_TYPE_NUMBER => __('Number'),
                                Attribute::ATTRIBUTE_TYPE_BOOLEAN => __('Boolean'),
                                Attribute::ATTRIBUTE_TYPE_SELECT => __('Select (Single Choice)'),
                                Attribute::ATTRIBUTE_TYPE_MULTISELECT => __('Multiselect (Multiple Choice)'),
                                default => (string) $state,
                            }),
                        TextEntry::make('scope')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                Attribute::SCOPE_PRODUCT => __('Product (Global Specification)'),
                                Attribute::SCOPE_VARIANT => __('Variant (Variant Option)'),
                                default => (string) $state,
                            }),
                        TextEntry::make('description')
                            ->placeholder(__('—'))
                            ->columnSpanFull(),
                    ]),

                Section::make('Behavior & Order')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ])
                    ->schema([
                        TextEntry::make('sort_order'),
                        IconEntry::make('is_required')->boolean(),
                        IconEntry::make('is_active')->boolean(),
                    ]),

                Section::make('Attribute Insights')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextEntry::make('values_count')
                            ->label('Predefined Values')
                            ->state(fn (Attribute $record): string => $record->values()->count().' values'),
                        TextEntry::make('assignments_count')
                            ->label('Assigned Items')
                            ->state(function (Attribute $record): string {
                                $count = $record->scope === Attribute::SCOPE_PRODUCT
                                    ? $record->productAttributeValues()->count()
                                    : $record->variantAttributeValues()->count();

                                $unit = $record->scope === Attribute::SCOPE_PRODUCT ? 'products' : 'variants';

                                return "{$count} {$unit}";
                            }),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ]),
            ]);
    }
}
