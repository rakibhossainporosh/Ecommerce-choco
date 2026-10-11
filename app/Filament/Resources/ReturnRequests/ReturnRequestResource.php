<?php

namespace App\Filament\Resources\ReturnRequests;

use App\Enums\ReturnRequestStatus;
use App\Filament\Resources\ReturnRequests\Pages\EditReturnRequest;
use App\Filament\Resources\ReturnRequests\Pages\ListReturnRequests;
use App\Filament\Resources\ReturnRequests\Pages\ViewReturnRequest;
use App\Filament\Resources\ReturnRequests\Schemas\ReturnRequestForm;
use App\Filament\Resources\ReturnRequests\Schemas\ReturnRequestInfolist;
use App\Filament\Resources\ReturnRequests\Tables\ReturnRequestsTable;
use App\Models\ReturnRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Return Requests';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', ReturnRequestStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending return requests';
    }

    public static function form(Schema $schema): Schema
    {
        return ReturnRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReturnRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReturnRequestsTable::configure($table);
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
            'index' => ListReturnRequests::route('/'),
            'view' => ViewReturnRequest::route('/{record}'),
            'edit' => EditReturnRequest::route('/{record}/edit'),
        ];
    }
}
