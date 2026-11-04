<?php

namespace App\Filament\Resources\Couriers;

use App\Filament\Concerns\TranslatesResourceNavigation;
use App\Filament\Resources\Couriers\Schemas\CourierForm;
use App\Models\Courier;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourierResource extends Resource
{
    use TranslatesResourceNavigation;

    protected static ?string $model = Courier::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static \UnitEnum|string|null $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Couriers';

    protected static ?string $modelLabel = 'Courier';

    protected static ?string $pluralModelLabel = 'Couriers';

    public static function form(Schema $schema): Schema
    {
        return CourierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('api_base_url')
                    ->label('API URL')
                    ->placeholder(__('—'))
                    ->toggleable()
                    ->alignCenter(),
                IconColumn::make('is_active')
                    ->label('Is active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
            ])
            ->paginated(! app()->runningUnitTests())
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCouriers::route('/'),
            'create' => Pages\CreateCourier::route('/create'),
            'edit' => Pages\EditCourier::route('/{record}/edit'),
        ];
    }
}
