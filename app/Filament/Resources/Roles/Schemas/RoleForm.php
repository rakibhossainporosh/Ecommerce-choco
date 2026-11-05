<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Support\AdminAccess;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Role')
                    ->description(__('Name this role. Built-in roles keep their names.'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Role name')
                            ->placeholder(__('e.g. Catalog viewer'))
                            ->prefixIcon(Heroicon::OutlinedShieldCheck)
                            ->helperText(__('People with this role see only the areas you check below.'))
                            ->required()
                            ->maxLength(50)
                            ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                            ->disabled(fn (?Role $record): bool => $record !== null && in_array($record->name, AdminAccess::SYSTEM_ROLES, true))
                            ->dehydrated(fn (?Role $record): bool => $record === null || ! in_array($record->name, AdminAccess::SYSTEM_ROLES, true))
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? trim($state) : null)
                            ->rules([
                                fn (?Role $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    $name = trim((string) $value);

                                    if (in_array($name, AdminAccess::SYSTEM_ROLES, true) && $record?->name !== $name) {
                                        $fail(__('This role name is reserved.'));
                                    }
                                },
                            ]),
                    ]),

                Section::make('Permissions')
                    ->description(__('Checked areas appear in the sidebar and on the dashboard. Everything else stays hidden.'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->visible(fn (?Role $record): bool => $record?->name !== 'Admin')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'lg' => 2,
                        ])->schema(self::permissionFields()),
                    ]),

                Section::make('Permissions')
                    ->description(__('This role is the store owner. Access is always on.'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->visible(fn (?Role $record): bool => $record?->name === 'Admin')
                    ->schema([
                        Placeholder::make('full_access')
                            ->hiddenLabel()
                            ->content(__('Admin can open every area. These permissions are not edited here.')),
                    ]),
            ]);
    }

    /**
     * @return list<Section>
     */
    protected static function permissionFields(): array
    {
        $fields = [];

        foreach (AdminAccess::permissionGroups() as $prefix => $group) {
            $fields[] = Section::make($group['label'])
                ->compact()
                ->schema([
                    CheckboxList::make('permission_groups.'.AdminAccess::stateKey($prefix))
                        ->label($group['label'])
                        ->translateLabel(false)
                        ->hiddenLabel()
                        ->options($group['options'])
                        ->columns([
                            'default' => 1,
                            'sm' => 2,
                        ])
                        ->bulkToggleable(),
                ]);
        }

        return $fields;
    }
}
