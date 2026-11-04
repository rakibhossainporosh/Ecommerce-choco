<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                View::make('filament.infolists.product-detail-view')
                    ->columnSpanFull(),
            ]);
    }
}
