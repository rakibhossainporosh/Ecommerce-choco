<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Support\AdminAccess;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Role;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Role')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('users_count')
                            ->label('Users')
                            ->state(fn (Role $record): string => (string) ($record->users_count ?? $record->users()->count())),
                        TextEntry::make('permissions.name')
                            ->label('Access')
                            ->badge()
                            ->placeholder(__('Full access'))
                            ->formatStateUsing(fn (string $state): string => AdminAccess::permissionLabel($state))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
