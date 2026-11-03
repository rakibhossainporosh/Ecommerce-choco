<script setup>
import { computed } from 'vue';
import { useI18n } from '../../Composables/useI18n';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    activeCategory: { type: String, default: 'all' },
    activeSort: { type: String, default: 'newest' },
    isOffer: { type: Boolean, default: false },
});

defineEmits(['selectCategory', 'selectSort']);

const { t } = useI18n();

const sortOptions = computed(() => [
    { label: t('home.sort_newest'), value: 'newest' },
    { label: t('home.sort_price_asc'), value: 'price_asc' },
    { label: t('home.sort_price_desc'), value: 'price_desc' },
]);
</script>

<template>
    <div id="featured" class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-3">
        <div class="bg-white border border-[#d5e0ee] rounded-sm px-3 sm:px-4 py-3 flex flex-col lg:flex-row lg:items-center gap-3">
            <h2 class="text-xs font-black uppercase tracking-[0.14em] text-[#0f172a] shrink-0">
                {{ isOffer ? t('home.offers_title') : t('home.featured') }}
            </h2>

            <div class="flex-1 flex items-center gap-1.5 overflow-x-auto scrollbar-none">
                <button
                    type="button"
                    class="px-3 h-8 text-[10px] font-black uppercase tracking-[0.08em] rounded-sm border whitespace-nowrap"
                    :class="activeCategory === 'all' ? 'bg-[#1d4ed8] border-[#1d4ed8] text-white' : 'bg-[#f4f7fb] border-[#d5e0ee] text-[#3e516b] hover:border-[#1d4ed8]'"
                    @click="$emit('selectCategory', 'all')"
                >
                    {{ t('home.all') }}
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    type="button"
                    class="px-3 h-8 text-[10px] font-black uppercase tracking-[0.08em] rounded-sm border whitespace-nowrap"
                    :class="activeCategory === cat.slug ? 'bg-[#1d4ed8] border-[#1d4ed8] text-white' : 'bg-[#f4f7fb] border-[#d5e0ee] text-[#3e516b] hover:border-[#1d4ed8]'"
                    @click="$emit('selectCategory', cat.slug)"
                >
                    {{ cat.display_name || cat.name }}
                </button>
            </div>

            <select
                :value="activeSort"
                class="h-8 px-2 text-[10px] font-black uppercase tracking-[0.08em] bg-[#f4f7fb] border border-[#d5e0ee] rounded-sm text-[#0f172a] outline-none"
                @change="$emit('selectSort', $event.target.value)"
            >
                <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
        </div>
    </div>
</template>
