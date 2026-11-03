<div class="admin-clear-cache-wrap">
    @if ($canClear)
        <div
            x-data="{ confirming: false }"
            @keydown.escape.window="confirming = false"
        >
            <button
                type="button"
                class="admin-clear-cache"
                title="{{ __('Clear all caches') }}"
                aria-label="{{ __('Clear all caches') }}"
                @click="confirming = true"
                wire:loading.attr="disabled"
                wire:target="clear"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" wire:loading.remove wire:target="clear">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                </svg>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="admin-clear-cache-spin" wire:loading wire:target="clear">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                </svg>
                <span class="admin-clear-cache-label">{{ __('Clear cache') }}</span>
            </button>

            <template x-teleport="body">
                <div
                    class="admin-clear-cache-layer"
                    x-show="confirming"
                    x-cloak
                    @click="confirming = false"
                >
                    <div
                        class="admin-clear-cache-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="admin-clear-cache-title"
                        @click.stop
                    >
                        <h3 id="admin-clear-cache-title" class="admin-clear-cache-modal-title">
                            {{ __('Clear all caches?') }}
                        </h3>
                        <p class="admin-clear-cache-modal-text">
                            {{ __('This refreshes the website and admin so new changes show correctly. Your products, orders, and customers stay safe.') }}
                        </p>
                        <div class="admin-clear-cache-modal-actions">
                            <button
                                type="button"
                                class="admin-clear-cache-cancel"
                                @click="confirming = false"
                            >
                                {{ __('Cancel') }}
                            </button>
                            <button
                                type="button"
                                class="admin-clear-cache-confirm"
                                wire:click="clear"
                                @click="confirming = false"
                                wire:loading.attr="disabled"
                                wire:target="clear"
                            >
                                {{ __('Clear now') }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
