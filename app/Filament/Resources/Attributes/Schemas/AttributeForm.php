<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Models\Attribute;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AttributeForm
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
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(['alpha_dash']),

                Select::make('type')
                    ->label('Type')
                    ->options([
                        Attribute::ATTRIBUTE_TYPE_TEXT => 'Text',
                        Attribute::ATTRIBUTE_TYPE_TEXTAREA => 'Textarea',
                        Attribute::ATTRIBUTE_TYPE_NUMBER => 'Number',
                        Attribute::ATTRIBUTE_TYPE_BOOLEAN => 'Boolean',
                        Attribute::ATTRIBUTE_TYPE_SELECT => 'Select',
                        Attribute::ATTRIBUTE_TYPE_MULTISELECT => 'Multiselect',
                    ])
                    ->required()
                    ->native(false),

                Select::make('scope')
                    ->label('Scope')
                    ->options([
                        Attribute::SCOPE_PRODUCT => 'Product',
                        Attribute::SCOPE_VARIANT => 'Variant',
                    ])
                    ->required()
                    ->native(false),

                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_required')
                    ->label('Required')
                    ->default(false),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),
            ]);
    }
}
