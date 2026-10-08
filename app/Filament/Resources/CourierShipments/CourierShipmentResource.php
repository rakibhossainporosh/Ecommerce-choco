<?php

namespace App\Filament\Resources\CourierShipments;

use App\Enums\CourierShipmentStatus;
use App\Models\CourierShipment;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourierShipmentResource extends Resource
{
    protected static ?string $model = CourierShipment::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static \UnitEnum|string|null $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(2)->schema([
                    Select::make('order_id')
                        ->relationship('order', 'order_number')
                        ->searchable()
                        ->required()
                        ->disabledOn('edit'),
                    Select::make('courier_id')
                        ->relationship('courier', 'name')
                        ->searchable()
                        ->required(),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('tracking_number')
                        ->maxLength(255),
                    Select::make('status')
                        ->options(CourierShipmentStatus::class)
                        ->required(),
                ]),
                Grid::make(2)->schema([
                    DateTimePicker::make('shipped_at'),
                    DateTimePicker::make('delivered_at'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->searchable()
                    ->sortable()
                    ->label('Order #'),
                TextColumn::make('courier.name')
                    ->searchable()
                    ->sortable()
                    ->label('Courier'),
                TextColumn::make('tracking_number')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->sortable(),
                TextColumn::make('shipped_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated(! app()->runningUnitTests())
            ->filters([
                SelectFilter::make('courier_id')
                    ->relationship('courier', 'name')
                    ->label('Courier'),
                SelectFilter::make('status')
                    ->options(CourierShipmentStatus::class),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
            'index' => Pages\ListCourierShipments::route('/'),
            'create' => Pages\CreateCourierShipment::route('/create'),
            'edit' => Pages\EditCourierShipment::route('/{record}/edit'),
        ];
    }
}
