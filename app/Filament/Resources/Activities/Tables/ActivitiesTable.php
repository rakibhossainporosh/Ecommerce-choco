<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Support\UiText;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'default' => __('General log'),
                        null, '' => '',
                        default => __($state),
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => __('Created'),
                        'updated' => __('Updated'),
                        'deleted' => __('Deleted'),
                        'restored' => __('Restored'),
                        null, '' => '',
                        default => __($state),
                    })
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(function ($state, $record): string {
                        if (blank($state)) {
                            return '—';
                        }

                        return UiText::line(class_basename((string) $state)).' #'.$record->subject_id;
                    })
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label('Causer')
                    ->formatStateUsing(fn ($state): string => $state === 'Super Admin' ? __('Super Admin') : (string) ($state ?? ''))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
