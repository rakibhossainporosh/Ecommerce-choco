<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Enums\CouponType;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('value')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->type === CouponType::Percentage ? "{$state}%" : '৳'.number_format($state, 2)),
                TextColumn::make('used_count')
                    ->label('Usage')
                    ->formatStateUsing(fn ($state, $record) => $record->usage_limit ? "{$state} / {$record->usage_limit}" : $state)
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated(! app()->runningUnitTests())
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                SelectFilter::make('type')
                    ->options(CouponType::class),
                // Additional filters like Validity could be added here if needed
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
