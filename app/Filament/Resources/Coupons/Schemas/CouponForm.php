<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Coupon Details')
                    ->description(__('Code, name, discount type, and value.'))
                    ->icon(Heroicon::OutlinedTicket)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->regex('/^[A-Za-z0-9\-_]+$/')
                            ->validationMessages([
                                'regex' => __('The code may only contain letters, numbers, dashes, and underscores.'),
                            ])
                            ->dehydrateStateUsing(fn (string $state): string => strtoupper(trim($state)))
                            ->prefixIcon(Heroicon::OutlinedHashtag)
                            ->helperText(__('Letters, numbers, dashes, and underscores only.')),

                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon(Heroicon::OutlinedTag)
                            ->helperText(__('Internal label for your team.')),

                        Select::make('type')
                            ->label('Type')
                            ->options(CouponType::class)
                            ->required()
                            ->live()
                            ->prefixIcon(Heroicon::OutlinedAdjustmentsHorizontal),

                        TextInput::make('value')
                            ->label('Value')
                            ->required()
                            ->numeric()
                            ->prefixIcon(Heroicon::OutlinedCurrencyBangladeshi)
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($get('type') === CouponType::Percentage->value && $value > 100) {
                                        $fail(__('Percentage coupon value cannot exceed 100%.'));
                                    }
                                    if ($value < 0) {
                                        $fail(__('Value cannot be negative.'));
                                    }
                                    if ($get('type') === CouponType::Fixed->value && $value <= 0) {
                                        $fail(__('Fixed coupon value must be greater than zero.'));
                                    }
                                },
                            ]),
                    ]),

                Section::make('Usage Constraints')
                    ->description(__('Order minimums, caps, and usage limits.'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('minimum_order_amount')
                            ->label('Minimum order amount')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->prefixIcon(Heroicon::OutlinedBanknotes),

                        TextInput::make('maximum_discount_amount')
                            ->label('Maximum discount amount')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->prefixIcon(Heroicon::OutlinedReceiptPercent),

                        TextInput::make('usage_limit')
                            ->label('Total usage limit')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->prefixIcon(Heroicon::OutlinedHashtag)
                            ->helperText(__('Leave blank for unlimited usage.')),

                        TextInput::make('usage_limit_per_user')
                            ->label('Per-user usage limit')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->prefixIcon(Heroicon::OutlinedUser)
                            ->helperText(__('Leave blank for unlimited usage per user.')),
                    ]),

                Section::make('Validity')
                    ->description(__('Schedule and active status for this coupon.'))
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ])
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Starts at')
                            ->nullable(),

                        DateTimePicker::make('expires_at')
                            ->label('Expires at')
                            ->nullable()
                            ->afterOrEqual('starts_at'),

                        Toggle::make('is_active')
                            ->label('Active status')
                            ->helperText(__('Turn off to stop this coupon from being used.'))
                            ->inline(false)
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
