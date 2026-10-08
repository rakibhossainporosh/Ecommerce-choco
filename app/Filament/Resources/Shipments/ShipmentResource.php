<?php

namespace App\Filament\Resources\Shipments;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Filament\Resources\Shipments\Pages\ViewShipment;
use App\Filament\Resources\Shipments\Schemas\ShipmentForm;
use App\Filament\Resources\Shipments\Schemas\ShipmentInfolist;
use App\Filament\Resources\Shipments\Tables\ShipmentsTable;
use App\Models\Shipment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;
    protected static \UnitEnum|string|null $navigationGroup = 'Shipping';

    protected static ?string $navigationLabel = 'Shipments';

    protected static ?string $modelLabel = 'Shipment';

    protected static ?string $pluralModelLabel = 'Shipments';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ShipmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ShipmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShipmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
            'view' => ViewShipment::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['order', 'customer', 'shippingMethod', 'dispatchedBy']);
    }

    /**
     * Shipments are managed via status transitions; inline form edits are disabled.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Active in-transit or delivered shipments cannot be deleted.
     */
    public static function canDelete(Model $record): bool
    {
        if (! auth()->user()?->can('shipping.update')) {
            return false;
        }

        return $record instanceof Shipment
            ? in_array($record->status, [ShipmentStatus::Pending, ShipmentStatus::Cancelled], true)
            : false;
    }

    /**
     * Bulk deletion of shipments is prohibited.
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }
}
