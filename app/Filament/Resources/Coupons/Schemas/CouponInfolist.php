<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Coupon Details')
                    ->icon(Heroicon::OutlinedTicket)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextEntry::make('code')->weight('bold')->copyable(),
                        TextEntry::make('name'),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('value')
                            ->formatStateUsing(fn ($state, $record) => $record->type === CouponType::Percentage ? "{$state}%" : '৳'.number_format($state, 2)),
                        IconEntry::make('is_active')->boolean(),
                    ]),

                Section::make('Usage Constraints')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextEntry::make('minimum_order_amount')->money('BDT'),
                        TextEntry::make('maximum_discount_amount')->money('BDT'),
                        TextEntry::make('usage_limit')->placeholder(__('Unlimited')),
                        TextEntry::make('usage_limit_per_user')->placeholder(__('Unlimited')),
                    ]),

                Section::make('Statistics & Validity')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ])
                    ->schema([
                        TextEntry::make('used_count')->label('Times Used'),
                        TextEntry::make('starts_at')->dateTime(),
                        TextEntry::make('expires_at')->dateTime(),
                    ]),
            ]);
    }
}
