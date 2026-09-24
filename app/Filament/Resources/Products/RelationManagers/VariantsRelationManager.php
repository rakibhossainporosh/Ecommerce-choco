<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Models\Attribute;
use App\Models\ProductVariant;
use App\Models\Unit;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Product Variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Variant Name (Optional)')
                    ->placeholder('e.g. Standard, 100g Bar')
                    ->nullable()
                    ->maxLength(255),

                Select::make('unit_id')
                    ->label('Unit')
                    ->options(fn () => Unit::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (blank($value)) {
                                return;
                            }

                            $unit = Unit::withTrashed()->find($value);
                            if (! $unit || $unit->trashed() || ! $unit->is_active) {
                                $fail('Cannot assign an invalid, inactive, or soft-deleted unit.');
                            }
                        },
                    ]),

                TextInput::make('sku')
                    ->label('Variant SKU')
                    ->required()
                    ->maxLength(255)
                    ->unique(table: 'product_variants', column: 'sku', ignoreRecord: true)
                    ->rules(['alpha_dash']),

                TextInput::make('barcode')
                    ->label('Barcode (Optional)')
                    ->nullable()
                    ->maxLength(255)
                    ->unique(table: 'product_variants', column: 'barcode', ignoreRecord: true),

                TextInput::make('cost_price')
                    ->label('Cost Price (BDT)')
                    ->numeric()
                    ->prefix('BDT')
                    ->required()
                    ->minValue(0),

                TextInput::make('selling_price')
                    ->label('Selling Price (BDT)')
                    ->numeric()
                    ->prefix('BDT')
                    ->required()
                    ->minValue(0),

                TextInput::make('compare_at_price')
                    ->label('Compare at Price (BDT)')
                    ->numeric()
                    ->prefix('BDT')
                    ->nullable()
                    ->minValue(0)
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if ($value !== null && $value !== '') {
                                $selling = $get('selling_price');
                                if ($selling !== null && $selling !== '' && (float) $value < (float) $selling) {
                                    $fail('The compare at price must be greater than or equal to the selling price.');
                                }
                            }
                        },
                    ]),

                TextInput::make('unit_quantity')
                    ->label('Unit Quantity')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(0.001),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Section::make('Variant Attributes')
                    ->description('Configure dynamic attributes for this variant.')
                    ->components(fn () => ProductForm::getVariantAttributeComponents('variant_attributes')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Variant Name')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->sortable(),

                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->money('BDT')
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, RelationManager $livewire): Model {
                        return DB::transaction(function () use ($data, $livewire) {
                            $variantAttributes = $data['variant_attributes'] ?? [];
                            unset($data['variant_attributes']);

                            /** @var ProductVariant $variant */
                            $variant = $livewire->getOwnerRecord()->variants()->create($data);

                            $syncPayload = [];
                            foreach ($variantAttributes as $attrId => $val) {
                                if ($val !== null && $val !== '' && $val !== []) {
                                    $syncPayload[$attrId] = $val;
                                }
                            }

                            $variant->syncAttributes($syncPayload);

                            return $variant;
                        });
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, ProductVariant $record): array {
                        $record->loadMissing(['variantAttributeValues.attribute']);

                        $assignments = [];
                        foreach ($record->variantAttributeValues as $vav) {
                            $attrId = $vav->attribute_id;
                            $type = $vav->attribute?->type;

                            if ($type === Attribute::ATTRIBUTE_TYPE_MULTISELECT) {
                                $assignments[$attrId][] = $vav->attribute_value_id;
                            } elseif ($type === Attribute::ATTRIBUTE_TYPE_SELECT) {
                                $assignments[$attrId] = $vav->attribute_value_id;
                            } elseif ($type === Attribute::ATTRIBUTE_TYPE_TEXT || $type === Attribute::ATTRIBUTE_TYPE_TEXTAREA) {
                                $assignments[$attrId] = $vav->text_value;
                            } elseif ($type === Attribute::ATTRIBUTE_TYPE_NUMBER) {
                                $assignments[$attrId] = $vav->number_value;
                            } elseif ($type === Attribute::ATTRIBUTE_TYPE_BOOLEAN) {
                                $assignments[$attrId] = (bool) $vav->boolean_value;
                            }
                        }

                        $data['variant_attributes'] = $assignments;

                        return $data;
                    })
                    ->using(function (ProductVariant $record, array $data): Model {
                        return DB::transaction(function () use ($record, $data) {
                            $variantAttributes = $data['variant_attributes'] ?? [];
                            unset($data['variant_attributes']);

                            $record->update($data);

                            $syncPayload = [];
                            foreach ($variantAttributes as $attrId => $val) {
                                if ($val !== null && $val !== '' && $val !== []) {
                                    $syncPayload[$attrId] = $val;
                                }
                            }

                            $record->syncAttributes($syncPayload);

                            return $record;
                        });
                    }),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, ProductVariant $record): void {
                        if (! $record->canBeDeleted()) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete this variant because it is the only variant or active default.')
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
