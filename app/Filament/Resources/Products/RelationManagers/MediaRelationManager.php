<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Product Media';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        if ($ownerRecord instanceof ProductVariant) {
            return 'Variant Media';
        }

        return 'Product Media';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('products.view') ?? false;
    }

    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        if (! auth()->user()?->can('products.update')) {
            abort(403, 'Unauthorized to reorder media.');
        }

        /** @var Product|ProductVariant $owner */
        $owner = $this->getOwnerRecord();

        $owner->reorderMedia(array_map('intval', $order));

        $this->isTableReordering = false;

        Notification::make()
            ->success()
            ->title('Media reordered successfully.')
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->contentGrid([
                'default' => 1,
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 4,
            ])
            ->columns([
                ImageColumn::make('path')
                    ->label('Preview')
                    ->disk('public')
                    ->height('160px')
                    ->extraImgAttributes([
                        'class' => 'rounded-lg object-cover w-full shadow-sm',
                        'loading' => 'lazy',
                    ]),

                TextColumn::make('is_primary')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Primary Image' : 'Gallery Image')
                    ->weight(FontWeight::Bold),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->formatStateUsing(fn (int $state): string => "#{$state}")
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('alt_text')
                    ->label('Alt Text')
                    ->placeholder('No alt text added')
                    ->limit(40)
                    ->color('gray'),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Upload Media')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->visible(fn (): bool => auth()->user()?->can('products.update') ?? false)
                    ->form([
                        FileUpload::make('images')
                            ->label('Select Images')
                            ->helperText('Allowed formats: JPEG, JPG, PNG, WEBP. Maximum file size: 5 MB per image.')
                            ->multiple()
                            ->disk('public')
                            ->directory(fn (RelationManager $livewire): string => ($livewire->getOwnerRecord() instanceof ProductVariant ? 'variants/' : 'products/').$livewire->getOwnerRecord()->getKey())
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->storeFileNamesIn('original_filenames')
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        if (! auth()->user()?->can('products.update')) {
                            abort(403, 'Unauthorized to upload media.');
                        }

                        /** @var Product|ProductVariant $owner */
                        $owner = $livewire->getOwnerRecord();
                        $images = $data['images'] ?? [];
                        $originalFileNames = $data['original_filenames'] ?? [];

                        if (! is_array($images)) {
                            $images = [$images];
                        }

                        foreach ($images as $key => $filePath) {
                            if ($filePath instanceof UploadedFile) {
                                $ownerDir = ($owner instanceof ProductVariant ? 'variants/' : 'products/').$owner->getKey();
                                $extension = $filePath->guessExtension() ?: $filePath->getClientOriginalExtension();
                                $storedPath = $filePath->storeAs($ownerDir, (string) Str::uuid().'.'.strtolower($extension), 'public');
                                $dimensions = @getimagesize($filePath->getRealPath());

                                $owner->addMedia([
                                    'disk' => 'public',
                                    'path' => $storedPath,
                                    'original_name' => $filePath->getClientOriginalName(),
                                    'mime_type' => $filePath->getMimeType(),
                                    'size' => $filePath->getSize(),
                                    'width' => $dimensions ? $dimensions[0] : null,
                                    'height' => $dimensions ? $dimensions[1] : null,
                                ]);
                            } else {
                                $fullPath = Storage::disk('public')->path($filePath);
                                $dimensions = @getimagesize($fullPath);
                                $size = Storage::disk('public')->exists($filePath) ? Storage::disk('public')->size($filePath) : null;
                                $mimeType = Storage::disk('public')->exists($filePath) ? Storage::disk('public')->mimeType($filePath) : null;
                                $originalName = $originalFileNames[$key] ?? $originalFileNames[$filePath] ?? basename($filePath);

                                $owner->addMedia([
                                    'disk' => 'public',
                                    'path' => $filePath,
                                    'original_name' => $originalName,
                                    'mime_type' => $mimeType,
                                    'size' => $size,
                                    'width' => $dimensions ? $dimensions[0] : null,
                                    'height' => $dimensions ? $dimensions[1] : null,
                                ]);
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('Media uploaded successfully.')
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('setPrimary')
                    ->label('Set as Primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (Media $record): bool => ! $record->is_primary && (auth()->user()?->can('products.update') ?? false))
                    ->action(function (Media $record, RelationManager $livewire): void {
                        if (! auth()->user()?->can('products.update')) {
                            abort(403, 'Unauthorized to set primary media.');
                        }

                        /** @var Product|ProductVariant $owner */
                        $owner = $livewire->getOwnerRecord();
                        $owner->setPrimaryMedia($record);

                        Notification::make()
                            ->success()
                            ->title('Primary image updated.')
                            ->send();
                    }),

                Action::make('editAltText')
                    ->label('Edit Alt Text')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->visible(fn (): bool => auth()->user()?->can('products.update') ?? false)
                    ->form([
                        TextInput::make('alt_text')
                            ->label('Alternative Text (Alt Text)')
                            ->helperText('Describes the image for screen readers and SEO.')
                            ->maxLength(255)
                            ->nullable(),
                    ])
                    ->fillForm(fn (Media $record): array => [
                        'alt_text' => $record->alt_text,
                    ])
                    ->action(function (Media $record, array $data, RelationManager $livewire): void {
                        if (! auth()->user()?->can('products.update')) {
                            abort(403, 'Unauthorized to update alt text.');
                        }

                        /** @var Product|ProductVariant $owner */
                        $owner = $livewire->getOwnerRecord();
                        $owner->updateMediaAltText($record, $data['alt_text'] ?? null);

                        Notification::make()
                            ->success()
                            ->title('Alt text updated.')
                            ->send();
                    }),

                DeleteAction::make()
                    ->label('Delete')
                    ->modalHeading('Delete Media')
                    ->modalDescription('Are you sure you want to delete this media item? If this is the primary image, the next image in sort order will automatically be promoted to primary.')
                    ->visible(fn (): bool => auth()->user()?->can('products.update') ?? false)
                    ->action(function (Media $record, RelationManager $livewire): void {
                        if (! auth()->user()?->can('products.update')) {
                            abort(403, 'Unauthorized to delete media.');
                        }

                        /** @var Product|ProductVariant $owner */
                        $owner = $livewire->getOwnerRecord();
                        $disk = $record->disk;
                        $path = $record->path;

                        $owner->deleteMedia($record);

                        // Physical cleanup after DB transaction commit, with shared-reference protection
                        Media::deletePhysicalFileIfUnreferenced($disk, $path);

                        Notification::make()
                            ->success()
                            ->title('Media deleted successfully.')
                            ->send();
                    }),
            ]);
    }
}
