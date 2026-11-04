<div class="variant-media-manager">
    {{-- Header info --}}
    <div class="vmm-header">
        <div>
            <h3 class="vmm-title">
                {{ $variant->name ? $variant->name.' ('.$variant->sku.')' : $variant->sku }}
            </h3>
            <p class="vmm-subtitle">
                {{ __('Variant photos, separate from the product photos') }}
            </p>
        </div>
        <div>
            @if ($ownMedia->isNotEmpty())
                <span class="vmm-badge vmm-badge-success">
                    {{ $ownMedia->count() }} image(s) uploaded
                </span>
            @else
                <span class="vmm-badge vmm-badge-warning">
                    {{ __('Using the product photos') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Upload Form --}}
    @can('products.update')
        <div class="vmm-upload-card">
            <h4 class="vmm-section-title">
                <svg class="vmm-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                {{ __('Upload variant photos') }}
            </h4>

            <label class="vmm-dropzone" for="vmm-file-input-{{ $variant->id }}">
                <input
                    id="vmm-file-input-{{ $variant->id }}"
                    type="file"
                    wire:model="uploads"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    class="vmm-file-input-hidden"
                />
                <span class="vmm-dropzone-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="28" height="28">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                </span>
                <span class="vmm-dropzone-title">{{ __('Click to choose photos') }}</span>
                <span class="vmm-dropzone-hint">{{ __('JPEG, JPG, PNG or WEBP · up to 5 MB each') }}</span>
            </label>

            <div
                wire:loading.flex
                wire:target="uploads"
                class="vmm-progress"
            >
                {{ __('Preparing selected photos…') }}
            </div>

            @if (count($selectedUploads) > 0)
                <div class="vmm-selected">
                    <div class="vmm-selected-header">
                        <p class="vmm-selected-title">
                            {{ trans_choice(':count photo selected — click Save photos to add them to this variant.|:count photos selected — click Save photos to add them to this variant.', count($selectedUploads), ['count' => count($selectedUploads)]) }}
                        </p>
                        <button type="button" wire:click="clearUploads" class="vmm-link-clear">
                            {{ __('Clear') }}
                        </button>
                    </div>

                    <div class="vmm-selected-grid">
                        @foreach ($selectedUploads as $item)
                            <div class="vmm-selected-item">
                                @if ($item['preview'])
                                    <img src="{{ $item['preview'] }}" alt="{{ $item['name'] }}" class="vmm-selected-img" />
                                @else
                                    <div class="vmm-selected-placeholder">IMG</div>
                                @endif
                                <div class="vmm-selected-meta">
                                    <span class="vmm-selected-name" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                                    <span class="vmm-selected-size">{{ $item['size'] }}</span>
                                </div>
                                <button
                                    type="button"
                                    wire:click="removeUpload({{ $item['index'] }})"
                                    class="vmm-selected-remove"
                                    title="{{ __('Remove') }}"
                                >
                                    &times;
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="vmm-upload-actions">
                        <button
                            type="button"
                            wire:click="savePhotos"
                            wire:loading.attr="disabled"
                            wire:target="savePhotos,uploads"
                            class="vmm-btn vmm-btn-primary"
                        >
                            <span wire:loading.remove wire:target="savePhotos">{{ __('Save photos to variant') }}</span>
                            <span wire:loading wire:target="savePhotos">{{ __('Saving…') }}</span>
                        </button>
                    </div>
                </div>
            @endif

            <p class="vmm-help">
                {{ __('These photos belong only to this variant. The first photo becomes the main photo.') }}
            </p>
            @error('uploads')
                <p class="vmm-error">{{ $message }}</p>
            @enderror
            @foreach ($errors->get('uploads.*') as $messages)
                @foreach ($messages as $message)
                    <p class="vmm-error">{{ $message }}</p>
                @endforeach
            @endforeach
        </div>
    @endcan

    {{-- Fallback Warning & Preview --}}
    @if ($ownMedia->isEmpty())
        <div class="vmm-fallback-card">
            <div class="vmm-fallback-message">
                <svg class="vmm-icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h5 class="vmm-fallback-title">
                        {{ __('No photos for this variant yet') }}
                    </h5>
                    <p class="vmm-fallback-text">
                        {{ __('This variant has no photos of its own. The shop will show the product photos instead.') }}
                    </p>
                </div>
            </div>

            @if ($fallbackMedia->isNotEmpty())
                <div class="vmm-fallback-gallery">
                    <p class="vmm-gallery-label">{{ __('Photos from the product') }}</p>
                    <div class="vmm-grid">
                        @foreach ($fallbackMedia as $fb)
                            <div class="vmm-thumb">
                                <img src="{{ $fb->url() }}" alt="{{ $fb->alt_text }}" class="vmm-thumb-img" />
                                <div class="vmm-thumb-caption">
                                    {{ $fb->original_name }}
                                </div>
                                @if ($fb->is_primary)
                                    <span class="vmm-thumb-badge">
                                        {{ __('Main product photo') }}
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
        <div class="vmm-own-gallery">
            <h4 class="vmm-gallery-heading">
                <span>{{ __('Variant photos (:count)', ['count' => $ownMedia->count()]) }}</span>
                <span class="vmm-gallery-hint">{{ __('Use the arrows to change the order.') }}</span>
            </h4>

            <div class="vmm-cards">
                @foreach ($ownMedia as $index => $media)
                    <div class="vmm-card">
                        <div class="vmm-card-media">
                            <img
                                src="{{ $media->url() }}"
                                alt="{{ $media->alt_text }}"
                                class="vmm-card-img"
                            />
                            @if ($media->is_primary)
                                <span class="vmm-card-badge vmm-card-badge-primary">
                                    {{ __('Main photo') }}
                                </span>
                            @else
                                <span class="vmm-card-badge vmm-card-badge-muted">
                                    {{ __('Extra photo') }}
                                </span>
                            @endif
                            <span class="vmm-card-order">
                                #{{ $media->sort_order }}
                            </span>
                        </div>

                        <div class="vmm-card-body">
                            <div>
                                @if ($editingMediaId === $media->id)
                                    <div class="vmm-alt-edit">
                                        <label class="vmm-label">{{ __('Photo description') }}</label>
                                        <input
                                            type="text"
                                            wire:model="editingAltText"
                                            class="vmm-input"
                                            placeholder="{{ __('Short description of the photo') }}"
                                        />
                                        <div class="vmm-alt-actions">
                                            <button
                                                type="button"
                                                wire:click="saveAltText({{ $media->id }})"
                                                class="vmm-btn vmm-btn-primary vmm-btn-xs"
                                            >
                                                {{ __('Save') }}
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="cancelEditAltText"
                                                class="vmm-btn vmm-btn-secondary vmm-btn-xs"
                                            >
                                                {{ __('Cancel') }}
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="vmm-alt-row">
                                        <p class="vmm-alt-text">
                                            {{ $media->alt_text ? '"'.$media->alt_text.'"' : __('No photo description') }}
                                        </p>
                                        @can('products.update')
                                            <button
                                                type="button"
                                                wire:click="startEditAltText({{ $media->id }}, '{{ addslashes($media->alt_text ?? '') }}')"
                                                class="vmm-icon-btn"
                                                title="{{ __('Edit photo description') }}"
                                            >
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                        @endcan
                                    </div>
                                @endif
                            </div>

                            @can('products.update')
                                <div class="vmm-card-footer">
                                    @if (! $media->is_primary)
                                        <button
                                            type="button"
                                            wire:click="setPrimary({{ $media->id }})"
                                            class="vmm-link-action vmm-link-warning"
                                        >
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                            {{ __('Make this the main photo') }}
                                        </button>
                                    @else
                                        <span class="vmm-link-action vmm-link-success">
                                            <svg fill="currentColor" viewBox="0 0 20 20" width="14" height="14" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            {{ __('Main photo') }}
                                        </span>
                                    @endif

                                    <div class="vmm-card-tools">
                                        @if ($index > 0)
                                            <button
                                                type="button"
                                                wire:click="moveUp({{ $media->id }})"
                                                class="vmm-icon-btn"
                                                title="{{ __('Move up') }}"
                                            >
                                                &uarr;
                                            </button>
                                        @endif
                                        @if ($index < $ownMedia->count() - 1)
                                            <button
                                                type="button"
                                                wire:click="moveDown({{ $media->id }})"
                                                class="vmm-icon-btn"
                                                title="{{ __('Move down') }}"
                                            >
                                                &darr;
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            wire:click="confirmDeleteMedia({{ $media->id }})"
                                            class="vmm-icon-btn vmm-icon-btn-danger"
                                            title="{{ __('Delete photo') }}"
                                        >
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
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

    @if ($confirmingDeleteMediaId)
        <div
            class="vmm-confirm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="vmm-confirm-title-{{ $variant->id }}"
            wire:keydown.escape.window="cancelDeleteMedia"
        >
            <button
                type="button"
                class="vmm-confirm-backdrop"
                wire:click="cancelDeleteMedia"
                aria-label="{{ __('Cancel') }}"
            ></button>
            <div class="vmm-confirm-panel">
                <h4 id="vmm-confirm-title-{{ $variant->id }}" class="vmm-confirm-title">
                    {{ __('Delete photo') }}
                </h4>
                <p class="vmm-confirm-text">
                    {{ __('Delete this photo? If it is the main photo, the next one will become the main photo.') }}
                </p>
                <div class="vmm-confirm-actions">
                    <button
                        type="button"
                        wire:click="cancelDeleteMedia"
                        class="vmm-btn vmm-btn-secondary"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="button"
                        wire:click="deleteMedia({{ $confirmingDeleteMediaId }})"
                        wire:loading.attr="disabled"
                        wire:target="deleteMedia"
                        class="vmm-btn vmm-btn-danger"
                    >
                        <span wire:loading.remove wire:target="deleteMedia">{{ __('Delete') }}</span>
                        <span wire:loading wire:target="deleteMedia">{{ __('Deleting…') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
