<?php

namespace App\Filament\Resources\Settings\Tables;

use App\Enums\SettingType;
use App\Models\Setting;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('key')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('label')
                    ->sortable()
                    ->searchable()
                    ->limit(30),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('value')
                    ->limit(50)
                    ->searchable(),
                IconColumn::make('is_public')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_system')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(function () {
                        return Setting::query()
                            ->select('group')
                            ->distinct()
                            ->pluck('group', 'group')
                            ->toArray();
                    }),
                SelectFilter::make('type')
                    ->options(SettingType::class),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->paginated(! app()->runningUnitTests())
            ->defaultSort('group', 'asc');
    }
}
