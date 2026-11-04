<?php

namespace App\Livewire;

use App\Models\Media;
use App\Models\ProductVariant;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class VariantMediaManager extends Component
{
    use WithFileUploads;

    public ProductVariant $variant;

    /** @var array<int, TemporaryUploadedFile|UploadedFile> */
    public array $uploads = [];

    public ?int $editingMediaId = null;

    public string $editingAltText = '';

    public ?int $confirmingDeleteMediaId = null;

    public function mount(ProductVariant $variant): void
    {
        $this->variant = $variant;
    }

    public function confirmDeleteMedia(int $mediaId): void
    {
        $this->confirmingDeleteMediaId = $mediaId;
    }

    public function cancelDeleteMedia(): void
    {
        $this->confirmingDeleteMediaId = null;
    }

    public function removeUpload(int $index): void
    {
        if (! array_key_exists($index, $this->uploads)) {
            return;
        }

        $uploads = $this->uploads;
        unset($uploads[$index]);
        $this->uploads = array_values($uploads);
    }

    public function clearUploads(): void
    {
        $this->reset('uploads');
    }

    /**
     * @return list<array{index: int, name: string, size: string, preview: ?string}>
     */
    public function selectedUploadPreviews(): array
    {
        $previews = [];

        foreach ($this->uploads as $index => $file) {
            if (! $file instanceof TemporaryUploadedFile && ! $file instanceof UploadedFile) {
                continue;
            }

            $preview = null;

            if ($file instanceof TemporaryUploadedFile) {
                try {
                    $preview = $file->temporaryUrl();
                } catch (\Throwable) {
                    $preview = null;
                }
            }

            $bytes = (int) ($file->getSize() ?: 0);
            $sizeLabel = $bytes >= 1048576
                ? number_format($bytes / 1048576, 1).' MB'
                : number_format(max($bytes / 1024, 0.1), 1).' KB';

            $previews[] = [
                'index' => $index,
                'name' => $file->getClientOriginalName(),
                'size' => $sizeLabel,
                'preview' => $preview,
            ];
        }

        return $previews;
    }

    /**
     * Persist staged temporary uploads to this variant.
     *
     * Named savePhotos (not upload) because Livewire reserves $wire.upload()
     * for the client-side file-upload API — wire:click="upload" never hits PHP.
     */
    public function savePhotos(): void
    {
        if (! auth()->user()?->can('products.update')) {
            abort(403, 'Unauthorized to upload variant media.');
        }

        $this->validate([
            'uploads' => ['required', 'array', 'min:1'],
            'uploads.*' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:5120',
            ],
        ], [
            'uploads.required' => __('Select at least one photo first.'),
            'uploads.min' => __('Select at least one photo first.'),
            'uploads.*.image' => 'The uploaded file must be a valid image.',
            'uploads.*.mimes' => 'Images must be in jpeg, jpg, png, or webp format.',
            'uploads.*.mimetypes' => 'Images must be in jpeg, jpg, png, or webp format.',
            'uploads.*.max' => 'Each image must not exceed 5 MB in size.',
        ]);

        $uploadedCount = 0;

        foreach ($this->uploads as $file) {
            $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
            $path = $file->storeAs(
                "variants/{$this->variant->id}",
                (string) Str::uuid().'.'.strtolower($extension),
                'public'
            );
            $fullPath = Storage::disk('public')->path($path);
            $dimensions = @getimagesize($fullPath);

            $this->variant->addMedia([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $dimensions ? $dimensions[0] : null,
                'height' => $dimensions ? $dimensions[1] : null,
            ]);

            $uploadedCount++;
        }

        $this->reset('uploads');
        $this->variant->refresh();
        $this->dispatch('media-updated');

        Notification::make()
            ->title(__('Variant photos uploaded'))
            ->body(trans_choice(':count photo saved to this variant.|:count photos saved to this variant.', $uploadedCount, ['count' => $uploadedCount]))
            ->success()
            ->send();
    }

    public function setPrimary(int $mediaId): void
    {
        if (! auth()->user()?->can('products.update')) {
            abort(403, 'Unauthorized to set primary media.');
        }

        $this->variant->setPrimaryMedia($mediaId);
        $this->variant->refresh();
        $this->dispatch('media-updated');
    }

    public function startEditAltText(int $mediaId, ?string $current): void
    {
        $this->editingMediaId = $mediaId;
        $this->editingAltText = $current ?? '';
    }

    public function cancelEditAltText(): void
    {
        $this->editingMediaId = null;
        $this->editingAltText = '';
    }

    public function saveAltText(int $mediaId): void
    {
        if (! auth()->user()?->can('products.update')) {
            abort(403, 'Unauthorized to update alt text.');
        }

        $this->variant->updateMediaAltText($mediaId, $this->editingAltText ?: null);
        $this->editingMediaId = null;
        $this->editingAltText = '';
        $this->variant->refresh();
        $this->dispatch('media-updated');
    }

    public function deleteMedia(int $mediaId): void
    {
        if (! auth()->user()?->can('products.update')) {
            abort(403, 'Unauthorized to delete media.');
        }

        $media = $this->variant->media()->findOrFail($mediaId);
        $disk = $media->disk;
        $path = $media->path;

        $this->variant->deleteMedia($media);

        // After DB transaction commit: safely purge physical file if unreferenced
        Media::deletePhysicalFileIfUnreferenced($disk, $path);

        $this->confirmingDeleteMediaId = null;
        $this->variant->refresh();
        $this->dispatch('media-updated');

        Notification::make()
            ->title(__('Photo deleted'))
            ->success()
            ->send();
    }

    public function reorder(array $orderedIds): void
    {
        if (! auth()->user()?->can('products.update')) {
            abort(403, 'Unauthorized to reorder media.');
        }

        $this->variant->reorderMedia(array_map('intval', $orderedIds));
        $this->variant->refresh();
        $this->dispatch('media-updated');
    }

    public function moveUp(int $mediaId): void
    {
        if (! auth()->user()?->can('products.update')) {
            abort(403, 'Unauthorized to reorder media.');
        }

        $ids = $this->variant->orderedMedia()->pluck('id')->all();
        $index = array_search($mediaId, $ids, true);
        if ($index !== false && $index > 0) {
            $prev = $ids[$index - 1];
            $ids[$index - 1] = $mediaId;
            $ids[$index] = $prev;
            $this->variant->reorderMedia($ids);
            $this->variant->refresh();
            $this->dispatch('media-updated');
        }
    }

    public function moveDown(int $mediaId): void
    {
        if (! auth()->user()?->can('products.update')) {
            abort(403, 'Unauthorized to reorder media.');
        }

        $ids = $this->variant->orderedMedia()->pluck('id')->all();
        $index = array_search($mediaId, $ids, true);
        if ($index !== false && $index < count($ids) - 1) {
            $next = $ids[$index + 1];
            $ids[$index + 1] = $mediaId;
            $ids[$index] = $next;
            $this->variant->reorderMedia($ids);
            $this->variant->refresh();
            $this->dispatch('media-updated');
        }
    }

    public function render(): View
    {
        $ownMedia = $this->variant->orderedMedia()->get();
        $fallbackMedia = $ownMedia->isEmpty() ? $this->variant->getResolvedMedia() : collect();

        return view('livewire.variant-media-manager', [
            'ownMedia' => $ownMedia,
            'fallbackMedia' => $fallbackMedia,
            'selectedUploads' => $this->selectedUploadPreviews(),
        ]);
    }
}
