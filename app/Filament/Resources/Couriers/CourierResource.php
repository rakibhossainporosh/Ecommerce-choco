<?php

namespace App\Filament\Resources\Couriers;

use App\Models\Courier;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourierResource extends Resource
{
    protected static ?string $model = Courier::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';

    protected static \UnitEnum|string|null $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Courier Details')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ]),
                    TextInput::make('api_base_url')
                        ->label('API Base URL')
                        ->url()
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->default(true),
                ]),
                Section::make('API Credentials')->schema([
                    KeyValue::make('credentials')
                        ->keyLabel('Credential Key')
                        ->valueLabel('Credential Value')
                        ->formatStateUsing(fn () => []) // Security: Hide existing credentials
                        ->dehydrated(fn ($state) => filled($state)) // Only save if provided
                        ->helperText('Existing credentials are not displayed for security. Entering new credentials will overwrite existing ones completely.'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('api_base_url')->label('API URL')->toggleable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated(! app()->runningUnitTests())
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
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
            'index' => Pages\ListCouriers::route('/'),
            'create' => Pages\CreateCourier::route('/create'),
            'edit' => Pages\EditCourier::route('/{record}/edit'),
        ];
    }
}
