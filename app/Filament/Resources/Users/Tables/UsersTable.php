<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Support\AdminAccess;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->alignCenter()
                    ->placeholder(__('No role')),

                IconColumn::make('can_access_admin_panel')
                    ->label('Can sign in')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => AdminAccess::userIsDeletable($record))
                    ->before(function (DeleteAction $action, User $record): void {
                        if (! AdminAccess::userIsDeletable($record)) {
                            Notification::make()
                                ->danger()
                                ->title(__('You cannot remove yourself or the last Admin.'))
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records): void {
                            foreach ($records as $record) {
                                if (! $record instanceof User || ! AdminAccess::userIsDeletable($record)) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('You cannot remove yourself or the last Admin.'))
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading(__('No users yet'))
            ->emptyStateDescription(__('Add a person and give them one role. They will only see that role’s areas.'))
            ->emptyStateIcon(Heroicon::OutlinedIdentification)
            ->striped();
    }
}
