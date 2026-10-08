<?php

namespace App\Filament\Resources\ReturnRequests\Schemas;

use App\Enums\ReturnRequestStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReturnRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Return Request Details')->schema([
                Grid::make(2)->schema([
                    Select::make('order_id')
                        ->relationship('order', 'order_number')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->options(ReturnRequestStatus::class)
                        ->required(),
                    TextInput::make('refund_amount')
                        ->numeric()
                        ->prefix('$') // Assuming USD or generic currency
                        ->default(0)
                        ->required(),
                ]),
                Grid::make(1)->schema([
                    Textarea::make('reason')
                        ->required(),
                    Textarea::make('admin_note')
                        ->label('Admin Note (Internal)'),
                ]),
            ]),
        ]);
    }
}
