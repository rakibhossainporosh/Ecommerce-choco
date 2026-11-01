<?php

namespace App\Filament\Resources\AbandonedCarts\Schemas;

use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class AbandonedCartInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                View::make('filament.infolists.abandoned-cart-view')
                    ->columnSpanFull(),
            ]);
    }
}
