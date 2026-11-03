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
    <div id="featured" class="max-w-6xl mx-auto px-4 sm:px-6 pt-2 pb-8">
        <div class="flex flex-col gap-6 border-b border-[#d3e4dc] pb-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.24em] text-[#0e6b58] font-bold">{{ t('home.all') }}</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-[#14241e]">
                        {{ isOffer ? t('home.offers_title') : t('home.featured') }}
                    </h2>
                </div>
                <div class="relative inline-flex items-center">
                    <select
                        :value="activeSort"
                        class="appearance-none bg-transparent border-0 text-sm font-semibold text-[#3f5c52] pr-6 cursor-pointer outline-none"
                        @change="$emit('selectSort', $event.target.value)"
                    >
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-6 overflow-x-auto scrollbar-none">
                <button
                    type="button"
                    class="pb-2 text-sm font-semibold whitespace-nowrap border-b-2 transition-colors"
                    :class="activeCategory === 'all' ? 'text-[#0e6b58] border-[#0e6b58]' : 'text-[#6d887e] border-transparent hover:text-[#14241e]'"
                    @click="$emit('selectCategory', 'all')"
                >
                    {{ t('home.all') }}
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    type="button"
                    class="pb-2 text-sm font-semibold whitespace-nowrap border-b-2 transition-colors"
                    :class="activeCategory === cat.slug ? 'text-[#0e6b58] border-[#0e6b58]' : 'text-[#6d887e] border-transparent hover:text-[#14241e]'"
                    @click="$emit('selectCategory', cat.slug)"
                >
                    {{ cat.display_name || cat.name }}
                </button>
            </div>
        </div>
    </div>
</template>
