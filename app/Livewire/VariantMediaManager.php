<?php

namespace App\Livewire;

use App\Models\Media;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class VariantMediaManager extends Component
{
    use WithFileUploads;

    public ProductVariant $variant;

    /** @var array<UploadedFile> */
    public array $uploads = [];

    public ?int $editingMediaId = null;

    public string $editingAltText = '';

    public function mount(ProductVariant $variant): void
    {
        $this->variant = $variant;
    }

    public function upload(): void
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
            'uploads.*.image' => 'The uploaded file must be a valid image.',
            'uploads.*.mimes' => 'Images must be in jpeg, jpg, png, or webp format.',
            'uploads.*.mimetypes' => 'Images must be in jpeg, jpg, png, or webp format.',
            'uploads.*.max' => 'Each image must not exceed 5 MB in size.',
        ]);

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
        }

        $this->uploads = [];
        $this->variant->refresh();
        $this->dispatch('media-updated');
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

        $this->variant->refresh();
        $this->dispatch('media-updated');
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
        ]);
    }
}
