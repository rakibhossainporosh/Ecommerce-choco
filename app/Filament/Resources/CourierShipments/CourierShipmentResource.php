<?php

namespace App\Filament\Resources\CourierShipments;

use App\Enums\CourierShipmentStatus;
use App\Filament\Concerns\TranslatesResourceNavigation;
use App\Filament\Resources\CourierShipments\Schemas\CourierShipmentForm;
use App\Models\CourierShipment;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CourierShipmentResource extends Resource
{
    use TranslatesResourceNavigation;

    protected static ?string $model = CourierShipment::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static \UnitEnum|string|null $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Courier Shipments';

    protected static ?string $modelLabel = 'Courier Shipment';

    protected static ?string $pluralModelLabel = 'Courier Shipments';

    public static function form(Schema $schema): Schema
    {
        return CourierShipmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->searchable()
                    ->sortable()
                    ->label('Order #')
                    ->weight('bold')
                    ->copyable(),
                TextColumn::make('courier.name')
                    ->searchable()
                    ->sortable()
                    ->label('Courier')
                    ->alignCenter(),
                TextColumn::make('tracking_number')
                    ->label('Tracking number')
                    ->searchable()
                    ->copyable()
                    ->placeholder(__('—'))
                    ->alignCenter(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('shipped_at')
                    ->label('Shipped at')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder(__('Pending'))
                    ->toggleable()
                    ->alignCenter(),
                TextColumn::make('delivered_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder(__('Pending'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
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
            'index' => Pages\ListCourierShipments::route('/'),
            'create' => Pages\CreateCourierShipment::route('/create'),
            'edit' => Pages\EditCourierShipment::route('/{record}/edit'),
        ];
    }

    /**
     * Draft consignments can be removed; dispatched history is retained for audit.
     */
    public static function canDelete(Model $record): bool
    {
        /** @var CourierShipment $record */
        return $record->status === CourierShipmentStatus::Pending
            && parent::canDelete($record);
    }
}
