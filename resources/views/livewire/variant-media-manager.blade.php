<div class="space-y-6">
    {{-- Header info --}}
    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ $variant->name ? $variant->name . ' (' . $variant->sku . ')' : $variant->sku }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Variant Media Gallery &bull; Managed independently from product media
            </p>
        </div>
        <div class="text-xs">
            @if ($ownMedia->isNotEmpty())
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                    {{ $ownMedia->count() }} image(s) uploaded
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                    Using Product Fallback
                </span>
            @endif
        </div>
    </div>

    {{-- Upload Form --}}
    @can('products.update')
        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/80 space-y-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload Variant Images
            </h4>
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <input
                    type="file"
                    wire:model="uploads"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-xs text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-950 dark:file:text-primary-300"
                />
                <button
                    type="button"
                    wire:click="upload"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 text-xs font-semibold rounded-lg shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none disabled:opacity-50 transition"
                >
                    <span wire:loading.remove wire:target="upload">Upload</span>
                    <span wire:loading wire:target="upload">Uploading...</span>
                </button>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500">
                Allowed formats: JPEG, JPG, PNG, WEBP. Maximum: 5 MB per image. First uploaded image automatically becomes primary.
            </p>
            @error('uploads')
                <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
            @error('uploads.*')
                <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>
    @endcan

    {{-- Fallback Warning & Preview --}}
    @if ($ownMedia->isEmpty())
        <div class="p-4 rounded-xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/40 space-y-3">
            <div class="flex items-start gap-2.5">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h5 class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                        No Dedicated Variant Images
                    </h5>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                        This variant does not have its own images. Storefront will fall back to the product's media gallery.
                    </p>
                </div>
            </div>

            @if ($fallbackMedia->isNotEmpty())
                <div class="pt-2">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-2">Active Fallback Images (from Product):</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach ($fallbackMedia as $fb)
                            <div class="relative group rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk($fb->disk)->url($fb->path) }}" alt="{{ $fb->alt_text }}" class="w-full h-24 object-cover" />
                                <div class="p-1.5 text-[10px] text-gray-500 dark:text-gray-400 truncate">
                                    {{ $fb->original_name }}
                                </div>
                                @if ($fb->is_primary)
                                    <span class="absolute top-1 left-1 bg-amber-500 text-white text-[9px] px-1.5 py-0.5 rounded font-medium shadow-sm">
                                        Product Primary
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        {{-- Own Media Gallery --}}
        <div class="space-y-3">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 flex items-center justify-between">
                <span>Variant Images ({{ $ownMedia->count() }})</span>
                <span class="text-xs font-normal text-gray-500">Order by sort order</span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                @foreach ($ownMedia as $index => $media)
                    <div class="flex flex-col rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm transition hover:shadow-md">
                        {{-- Image with Badges --}}
                        <div class="relative bg-gray-100 dark:bg-gray-900">
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk($media->disk)->url($media->path) }}"
                                alt="{{ $media->alt_text }}"
                                class="w-full h-36 object-cover"
                            />
                            {{-- Primary badge --}}
                            @if ($media->is_primary)
                                <span class="absolute top-2 left-2 inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-600 text-white shadow-sm">
                                    Primary Image
                                </span>
                            @else
                                <span class="absolute top-2 left-2 inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-900/60 text-white backdrop-blur-sm">
                                    Gallery
                                </span>
                            @endif

                            {{-- Sort order badge --}}
                            <span class="absolute top-2 right-2 inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono bg-black/60 text-white">
                                #{{ $media->sort_order }}
                            </span>
                        </div>

                        {{-- Body: Alt Text --}}
                        <div class="p-3 flex-1 flex flex-col justify-between space-y-2">
                            <div>
                                @if ($editingMediaId === $media->id)
                                    <div class="space-y-1.5">
                                        <label class="text-[11px] font-medium text-gray-700 dark:text-gray-300">Edit Alt Text</label>
                                        <input
                                            type="text"
                                            wire:model="editingAltText"
                                            class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="Descriptive alt text"
                                        />
                                        <div class="flex items-center gap-1.5 pt-1">
                                            <button
                                                type="button"
                                                wire:click="saveAltText({{ $media->id }})"
                                                class="px-2 py-1 text-[11px] font-medium rounded bg-primary-600 text-white hover:bg-primary-700"
                                            >
                                                Save
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="cancelEditAltText"
                                                class="px-2 py-1 text-[11px] font-medium rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-start justify-between gap-1">
                                        <p class="text-xs text-gray-600 dark:text-gray-300 italic line-clamp-2">
                                            {{ $media->alt_text ? '"' . $media->alt_text . '"' : 'No alt text' }}
                                        </p>
                                        @can('products.update')
                                            <button
                                                type="button"
                                                wire:click="startEditAltText({{ $media->id }}, '{{ addslashes($media->alt_text ?? '') }}')"
                                                class="text-gray-400 hover:text-primary-600 shrink-0 p-1"
                                                title="Edit alt text"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                        @endcan
                                    </div>
                                @endif
                            </div>

                            {{-- Actions Footer --}}
                            @can('products.update')
                                <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                                    {{-- Primary Action --}}
                                    @if (! $media->is_primary)
                                        <button
                                            type="button"
                                            wire:click="setPrimary({{ $media->id }})"
                                            class="text-amber-600 dark:text-amber-400 hover:underline font-medium flex items-center gap-1"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                            Set Primary
                                        </button>
                                    @else
                                        <span class="text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            Primary
                                        </span>
                                    @endif

                                    {{-- Reorder & Delete --}}
                                    <div class="flex items-center gap-1">
                                        @if ($index > 0)
                                            <button
                                                type="button"
                                                wire:click="moveUp({{ $media->id }})"
                                                class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                                                title="Move up"
                                            >
                                                &uarr;
                                            </button>
                                        @endif
                                        @if ($index < $ownMedia->count() - 1)
                                            <button
                                                type="button"
                                                wire:click="moveDown({{ $media->id }})"
                                                class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                                                title="Move down"
                                            >
                                                &darr;
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            wire:click="deleteMedia({{ $media->id }})"
                                            wire:confirm="Are you sure you want to delete this media item? If this is the primary image, the next image will become primary."
                                            class="text-rose-500 hover:text-rose-700 p-1"
                                            title="Delete media"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
