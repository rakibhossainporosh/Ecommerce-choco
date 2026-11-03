<script setup>
import { Search, ChevronRight, Loader2 } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '../../Composables/useI18n';

defineProps({
    searchInput: { type: String, default: '' },
    suggestions: { type: Array, default: () => [] },
    isLoading: { type: Boolean, default: false },
    show: { type: Boolean, default: false },
    tone: { type: String, default: 'grove' },
});

defineEmits(['search', 'close']);

const { t } = useI18n();
</script>

<template>
    <div
        v-if="show && searchInput.trim()"
        class="absolute left-0 right-0 top-full mt-2 overflow-hidden z-50"
        :class="tone === 'harbor'
            ? 'bg-white border border-[#d5e0ee] shadow-lg rounded-sm'
            : 'bg-white border border-[#d3e4dc] shadow-[0_18px_40px_rgb(20_36_30/0.1)] rounded-2xl'"
    >
        <div v-if="isLoading" class="py-6 flex items-center justify-center gap-2.5 text-sm" :class="tone === 'harbor' ? 'text-[#3e516b]' : 'text-[#3f5c52]'">
            <Loader2 class="w-4 h-4 animate-spin" :class="tone === 'harbor' ? 'text-[#1d4ed8]' : 'text-[#0e6b58]'" />
            <span>{{ t('header.searching') }}</span>
        </div>

        <div v-else-if="suggestions.length > 0" class="flex flex-col">
            <div class="px-4 pt-3 pb-1.5 flex items-center justify-between text-[11px] font-bold uppercase tracking-wider" :class="tone === 'harbor' ? 'text-[#6d83a0]' : 'text-[#6d887e]'">
                <span>{{ t('header.products') }}</span>
                <span>{{ t('header.found', { count: suggestions.length }) }}</span>
            </div>
            <div class="max-h-[380px] overflow-y-auto" :class="tone === 'harbor' ? 'divide-y divide-[#e7eef8]' : 'divide-y divide-[#e7f1ec]'">
                <Link
                    v-for="item in suggestions"
                    :key="item.id"
                    :href="item.url"
                    @click="$emit('close')"
                    class="flex items-center gap-3.5 px-4 py-3 transition-colors group cursor-pointer"
                    :class="tone === 'harbor' ? 'hover:bg-[#f4f7fb]' : 'hover:bg-[#f3f7f5]'"
                >
                    <img
                        :src="item.image"
                        :alt="item.display_name || item.name"
                        class="w-12 h-12 object-cover shrink-0"
                        :class="tone === 'harbor' ? 'rounded-sm bg-[#e7eef8] border border-[#d5e0ee]' : 'rounded-xl bg-[#e7f1ec] border border-[#d3e4dc]'"
                    />
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate" :class="tone === 'harbor' ? 'text-[#0f172a] group-hover:text-[#1d4ed8]' : 'text-[#14241e] group-hover:text-[#0e6b58]'">
                            {{ item.display_name || item.name }}
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs font-medium" :class="tone === 'harbor' ? 'text-[#6d83a0]' : 'text-[#6d887e]'">{{ item.category_display_name || item.category_name }}</span>
                            <span class="text-sm font-bold" :class="tone === 'harbor' ? 'text-[#1d4ed8]' : 'text-[#0e6b58]'">৳ {{ Number(item.price).toLocaleString('en-US') }}</span>
                        </div>
                    </div>
                    <ChevronRight class="w-4 h-4 shrink-0" :class="tone === 'harbor' ? 'text-[#b9c9de]' : 'text-[#b7d0c6]'" />
                </Link>
            </div>
            <div class="p-2 border-t" :class="tone === 'harbor' ? 'bg-[#f4f7fb] border-[#d5e0ee]' : 'bg-[#f3f7f5] border-[#d3e4dc]'">
                <button
                    type="button"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold cursor-pointer"
                    :class="tone === 'harbor' ? 'text-[#1d4ed8]' : 'text-[#0e6b58]'"
                    @click="$emit('search')"
                >
                    <span>{{ t('header.view_all', { query: searchInput.trim() }) }}</span>
                </button>
            </div>
        </div>

        <div v-else class="py-8 px-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 flex items-center justify-center" :class="tone === 'harbor' ? 'rounded-sm bg-[#e7eef8] text-[#6d83a0]' : 'rounded-full bg-[#e7f1ec] text-[#6d887e]'">
                <Search class="w-5 h-5" />
            </div>
            <p class="text-sm font-medium" :class="tone === 'harbor' ? 'text-[#3e516b]' : 'text-[#3f5c52]'">
                {{ t('header.no_results', { query: searchInput.trim() }) }}
            </p>
        </div>
    </div>
</template>
