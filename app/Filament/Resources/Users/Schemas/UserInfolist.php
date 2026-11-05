<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Account')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('roles.name')
                            ->label('Role')
                            ->badge()
                            ->placeholder(__('No role')),
                        IconEntry::make('can_access_admin_panel')
                            ->label('Can sign in')
                            ->boolean(),
                    ]),
            ]);
    }
}
