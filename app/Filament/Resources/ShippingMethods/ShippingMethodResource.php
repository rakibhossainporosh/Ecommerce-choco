<?php

namespace App\Filament\Resources\ShippingMethods;

use App\Filament\Resources\ShippingMethods\Pages\ListShippingMethods;
use App\Filament\Resources\ShippingMethods\Schemas\ShippingMethodForm;
use App\Filament\Resources\ShippingMethods\Tables\ShippingMethodsTable;
use App\Models\ShippingMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Shipping Methods';

    protected static ?string $modelLabel = 'Shipping Method';

    protected static ?string $pluralModelLabel = 'Shipping Methods';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return ShippingMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingMethodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingMethods::route('/'),
        ];
    }
}
