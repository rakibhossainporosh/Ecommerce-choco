<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Reviewer')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('rating')
                    ->label('Rating')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => "★ {$state}")
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('—'),
                IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(! app()->runningUnitTests())
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->placeholder('All Reviews')
                    ->trueLabel('Approved Reviews Only')
                    ->falseLabel('Pending Moderation Only'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn ($record): bool => ! $record->is_approved)
                    ->action(function ($record) {
                        $record->update(['is_approved' => true]);
                        Notification::make()
                            ->title('Review approved successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Unapprove')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn ($record): bool => (bool) $record->is_approved)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_approved' => false]);
                        Notification::make()
                            ->title('Review set to unapproved')
                            ->warning()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('approve_selected')
                    ->label('Approve Selected')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $records->each->update(['is_approved' => true]);
                        Notification::make()
                            ->title('Selected reviews approved')
                            ->success()
                            ->send();
                    }),
                BulkAction::make('unapprove_selected')
                    ->label('Unapprove Selected')
                    ->icon('heroicon-m-x-mark')
                    ->color('warning')
                    ->action(function (Collection $records) {
                        $records->each->update(['is_approved' => false]);
                        Notification::make()
                            ->title('Selected reviews set to pending')
                            ->warning()
                            ->send();
                    }),
                DeleteBulkAction::make(),
            ]);
    }
}
