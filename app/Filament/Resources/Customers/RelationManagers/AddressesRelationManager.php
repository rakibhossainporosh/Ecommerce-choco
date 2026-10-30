<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\CustomerAddress;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Postal & Delivery Addresses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Address Type')
                    ->options([
                        'shipping' => 'Shipping Address',
                        'billing' => 'Billing Address',
                    ])
                    ->default('shipping')
                    ->required(),

                TextInput::make('name')
                    ->label('Recipient Name (Optional)')
                    ->maxLength(255)
                    ->placeholder('Leave blank to use customer name'),

                TextInput::make('phone')
                    ->label('Recipient Phone (Optional)')
                    ->tel()
                    ->maxLength(30)
                    ->placeholder('Leave blank to use customer phone'),

                TextInput::make('address_line')
                    ->label('Address Line (Street/House/Flat)')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('area')
                    ->label('Area / Thana')
                    ->required()
                    ->maxLength(100),

                TextInput::make('city')
                    ->label('City / District')
                    ->default('Dhaka')
                    ->required()
                    ->maxLength(100),

                TextInput::make('postcode')
                    ->label('Postal Code')
                    ->maxLength(20),

                TextInput::make('country')
                    ->label('Country')
                    ->default('Bangladesh')
                    ->required()
                    ->maxLength(100),

                Toggle::make('is_default')
                    ->label('Default Address for this Type')
                    ->default(false)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'shipping' => 'info',
                        'billing' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('address_line')
                    ->label('Address')
                    ->limit(40),

                TextColumn::make('area')
                    ->label('Area'),

                TextColumn::make('city')
                    ->label('City'),

                TextColumn::make('postcode')
                    ->label('Postcode')
                    ->placeholder('—'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('set_default')
                    ->label('Make Default')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->hidden(fn (CustomerAddress $record): bool => (bool) $record->is_default)
                    ->action(fn (CustomerAddress $record) => $record->setAsDefault()),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
