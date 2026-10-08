<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Orders\RelationManagers\ShipmentsRelationManager;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    
    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'customer.first_name', 'customer.last_name', 'customer.email'];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;
    protected static \UnitEnum|string|null $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $modelLabel = 'Order';

    protected static ?string $pluralModelLabel = 'Orders';

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
            ShipmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['items', 'statusHistories.changedBy', 'payments', 'shipments']);
    }

    /**
     * Orders originate exclusively from customer checkout flows; manual admin creation is disabled.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Orders are managed through lifecycle actions; arbitrary inline/form editing is prohibited.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Orders are immutable historical records; deletion is prohibited.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Bulk deletion of orders is prohibited.
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }
}
