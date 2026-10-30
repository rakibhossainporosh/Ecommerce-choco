<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Coupon Details')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('code')->weight('bold')->copyable(),
                    TextEntry::make('name'),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('value')
                        ->formatStateUsing(fn ($state, $record) => $record->type === CouponType::Percentage ? "{$state}%" : '৳'.number_format($state, 2)),
                    IconEntry::make('is_active')->boolean(),
                ]),
            ]),

            Section::make('Usage Constraints')->schema([
                Grid::make(4)->schema([
                    TextEntry::make('minimum_order_amount')->money('BDT'),
                    TextEntry::make('maximum_discount_amount')->money('BDT'),
                    TextEntry::make('usage_limit')->placeholder('Unlimited'),
                    TextEntry::make('usage_limit_per_user')->placeholder('Unlimited'),
                ]),
            ]),

            Section::make('Statistics & Validity')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('used_count')->label('Times Used'),
                    TextEntry::make('starts_at')->dateTime(),
                    TextEntry::make('expires_at')->dateTime(),
                ]),
            ]),
        ]);
    }
}
