<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
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
                    ->searchable()
                    ->alignCenter(),
                TextColumn::make('rating')
                    ->label('Rating')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => "★ {$state}")
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30)
                    ->placeholder(__('—'))
                    ->alignCenter(),
                IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(! app()->runningUnitTests())
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->placeholder(__('All Reviews'))
                    ->trueLabel(__('Approved Reviews Only'))
                    ->falseLabel(__('Pending Moderation Only')),
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
                            ->title(__('Review approved successfully'))
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
                            ->title(__('Review set to unapproved'))
                            ->warning()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('approve_selected')
                    ->label('Approve Selected')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $records->each->update(['is_approved' => true]);
                        Notification::make()
                            ->title(__('Selected reviews approved'))
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
                            ->title(__('Selected reviews set to pending'))
                            ->warning()
                            ->send();
                    }),
                DeleteBulkAction::make(),
            ]);
    }
}
