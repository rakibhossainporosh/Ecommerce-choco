<?php

namespace App\Filament\Resources\ReturnRequests\Tables;

use App\Enums\ReturnRequestStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReturnRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('refund_amount')
                    ->prefix('৳ ')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->paginated(! app()->runningUnitTests())
            ->filters([
                SelectFilter::make('status')
                    ->options(ReturnRequestStatus::class),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
