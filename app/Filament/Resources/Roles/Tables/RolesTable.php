<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Support\AdminAccess;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Role')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->alignCenter()
                    ->state(fn (Role $record): string => in_array($record->name, AdminAccess::SYSTEM_ROLES, true) ? __('Built-in') : __('Custom'))
                    ->color(fn (Role $record): string => in_array($record->name, AdminAccess::SYSTEM_ROLES, true) ? 'primary' : 'gray'),

                TextColumn::make('users_count')
                    ->label('Users')
                    ->badge()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label('Access')
                    ->formatStateUsing(function (mixed $state, Role $record): string {
                        if ($record->name === 'Admin') {
                            return __('Full access');
                        }

                        return __(':count permissions', ['count' => (int) $state]);
                    })
                    ->badge()
                    ->alignCenter()
                    ->color('info'),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Role $record): bool => AdminAccess::roleIsDeletable($record))
                    ->before(function (DeleteAction $action, Role $record): void {
                        if (! AdminAccess::roleIsDeletable($record)) {
                            Notification::make()
                                ->danger()
                                ->title(__('Built-in roles, and roles that still have users, stay in place.'))
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
                                if (! $record instanceof Role || ! AdminAccess::roleIsDeletable($record)) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('Built-in roles, and roles that still have users, stay in place.'))
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading(__('No roles yet'))
            ->emptyStateDescription(__('Create a role, then choose exactly what that role can see.'))
            ->emptyStateIcon(Heroicon::OutlinedShieldCheck)
            ->striped();
    }
}
