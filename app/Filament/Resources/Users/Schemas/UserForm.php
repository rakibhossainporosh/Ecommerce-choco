<?php

namespace App\Filament\Resources\Users\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Account')
                    ->description(__('This person signs in to the admin panel with one role.'))
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->placeholder(__('e.g. Nusrat Rahman'))
                            ->prefixIcon(Heroicon::OutlinedUser)
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->placeholder(__('name@store.com'))
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('role')
                            ->label('Role')
                            ->prefixIcon(Heroicon::OutlinedShieldCheck)
                            ->helperText(__('They can open only what this role allows.'))
                            ->options(fn (): array => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name', 'name')->all())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),

                        Toggle::make('can_access_admin_panel')
                            ->label('Can sign in')
                            ->helperText(__('Turn off to block this person without deleting the account.'))
                            ->default(true)
                            ->inline(false),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->prefixIcon(Heroicon::OutlinedLockClosed)
                            ->helperText(fn (string $operation): string => $operation === 'create'
                                ? __('At least 8 characters.')
                                : __('Leave blank to keep the current password.'))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255)
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    if (strlen((string) $value) < 8) {
                                        $fail(__('The password must be at least 8 characters.'));
                                    }

                                    if ($value !== $get('password_confirmation')) {
                                        $fail(__('The password confirmation does not match.'));
                                    }
                                },
                            ]),

                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->revealable()
                            ->prefixIcon(Heroicon::OutlinedLockClosed)
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ]),
            ]);
    }
}
