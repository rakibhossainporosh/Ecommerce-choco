<?php

namespace App\Filament\Resources\Attributes\Tables;

use App\Models\Attribute;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Attribute Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('Slug copied'))
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Attribute::ATTRIBUTE_TYPE_SELECT, Attribute::ATTRIBUTE_TYPE_MULTISELECT => 'info',
                        Attribute::ATTRIBUTE_TYPE_NUMBER => 'success',
                        Attribute::ATTRIBUTE_TYPE_BOOLEAN => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('scope')
                    ->label('Scope')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Attribute::SCOPE_VARIANT => 'purple',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('values_count')
                    ->counts('values')
                    ->label('Values')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        Attribute::ATTRIBUTE_TYPE_TEXT => __('Text'),
                        Attribute::ATTRIBUTE_TYPE_TEXTAREA => __('Textarea'),
                        Attribute::ATTRIBUTE_TYPE_NUMBER => __('Number'),
                        Attribute::ATTRIBUTE_TYPE_BOOLEAN => __('Boolean'),
                        Attribute::ATTRIBUTE_TYPE_SELECT => __('Select'),
                        Attribute::ATTRIBUTE_TYPE_MULTISELECT => __('Multiselect'),
                    ]),

                SelectFilter::make('scope')
                    ->options([
                        Attribute::SCOPE_PRODUCT => __('Product'),
                        Attribute::SCOPE_VARIANT => __('Variant'),
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Attribute $record): void {
                        if (! $record->canBeDeleted()) {
                            Notification::make()
                                ->danger()
                                ->title(__('Cannot delete this attribute because it has associated attribute values.'))
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
                                if (! $record->canBeDeleted()) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('Cannot delete this attribute because it has associated attribute values.'))
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
                ]),
            ]);
    }
}
