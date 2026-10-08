<?php

namespace App\Filament\Resources\ReturnRequests\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

class ReturnRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Return Request Information')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('order.order_number')->weight('bold'),
                    TextEntry::make('user.name')->weight('bold'),
                    TextEntry::make('status')->badge(),
                ]),
                Grid::make(2)->schema([
                    TextEntry::make('refund_amount')->prefix('৳ '),
                    TextEntry::make('created_at')->dateTime(),
                ]),
                Grid::make(1)->schema([
                    TextEntry::make('reason'),
                    TextEntry::make('admin_note')->placeholder('No Internal Note'),
                ]),
            ]),
        ]);
    }
}
