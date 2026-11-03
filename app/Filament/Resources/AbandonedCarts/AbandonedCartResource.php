<?php

namespace App\Filament\Resources\AbandonedCarts;

use App\Enums\AbandonedCartStatus;
use App\Filament\Concerns\TranslatesResourceNavigation;
use App\Filament\Resources\AbandonedCarts\Pages\ListAbandonedCarts;
use App\Filament\Resources\AbandonedCarts\Pages\ViewAbandonedCart;
use App\Filament\Resources\AbandonedCarts\Schemas\AbandonedCartInfolist;
use App\Filament\Resources\AbandonedCarts\Tables\AbandonedCartsTable;
use App\Models\AbandonedCart;
use App\Support\AdminNavigationBadge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AbandonedCartResource extends Resource
{
    use TranslatesResourceNavigation;

    protected static ?string $model = AbandonedCart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static \UnitEnum|string|null $navigationGroup = 'Customers & Marketing';

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = 'Abandoned carts';

    protected static ?string $modelLabel = 'Abandoned cart';

    protected static ?string $pluralModelLabel = 'Abandoned carts';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('orders.view');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return AbandonedCartInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbandonedCartsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        return AdminNavigationBadge::count(
            'abandoned_carts.open',
            static fn (): int => AbandonedCart::query()->where('status', AbandonedCartStatus::Open)->count(),
        );
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getRecordTitle(?Model $record): string
    {
        if (! $record instanceof AbandonedCart) {
            return __('Abandoned cart');
        }

        return $record->name ?: ($record->phone ?: __('Abandoned cart'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbandonedCarts::route('/'),
            'view' => ViewAbandonedCart::route('/{record}'),
        ];
    }
}
