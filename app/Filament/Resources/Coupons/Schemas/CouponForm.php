<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Coupon Details')->schema([
                Grid::make(2)->schema([
                    TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->regex('/^[A-Za-z0-9\-_]+$/')
                        ->validationMessages([
                            'regex' => 'The code may only contain letters, numbers, dashes, and underscores.',
                        ])
                        ->dehydrateStateUsing(fn (string $state): string => strtoupper(trim($state))),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->options(CouponType::class)
                        ->required()
                        ->live(),
                    TextInput::make('value')
                        ->required()
                        ->numeric()
                        ->rules([
                            fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($get('type') === CouponType::Percentage->value && $value > 100) {
                                    $fail('Percentage coupon value cannot exceed 100%.');
                                }
                                if ($value < 0) {
                                    $fail('Value cannot be negative.');
                                }
                                if ($get('type') === CouponType::Fixed->value && $value <= 0) {
                                    $fail('Fixed coupon value must be greater than zero.');
                                }
                            },
                        ]),
                ]),
            ]),

            Section::make('Usage Constraints')->schema([
                Grid::make(2)->schema([
                    TextInput::make('minimum_order_amount')
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                    TextInput::make('maximum_discount_amount')
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                    TextInput::make('usage_limit')
                        ->numeric()
                        ->minValue(1)
                        ->nullable()
                        ->hint('Leave blank for unlimited usage.'),
                    TextInput::make('usage_limit_per_user')
                        ->numeric()
                        ->minValue(1)
                        ->nullable()
                        ->hint('Leave blank for unlimited usage per user.'),
                ]),
            ]),

            Section::make('Validity')->schema([
                Grid::make(2)->schema([
                    DateTimePicker::make('starts_at')
                        ->nullable(),
                    DateTimePicker::make('expires_at')
                        ->nullable()
                        ->afterOrEqual('starts_at'),
                    Toggle::make('is_active')
                        ->default(true)
                        ->required(),
                ]),
            ]),
        ]);
    }
}
