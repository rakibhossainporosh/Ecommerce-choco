<?php

namespace App\Filament\Resources\ReturnRequests;

use App\Filament\Resources\ReturnRequests\Pages\CreateReturnRequest;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

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
            'create' => CreateReturnRequest::route('/create'),
            'view' => ViewReturnRequest::route('/{record}'),
            'edit' => EditReturnRequest::route('/{record}/edit'),
        ];
    }
}
