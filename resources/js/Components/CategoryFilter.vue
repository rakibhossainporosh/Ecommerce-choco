<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmCategoryFilter from './themes/WarmCategoryFilter.vue';
import GroveCategoryFilter from './themes/GroveCategoryFilter.vue';
import HarborCategoryFilter from './themes/HarborCategoryFilter.vue';

defineProps({
    categories: { type: Array, default: () => [] },
    activeCategory: { type: String, default: 'all' },
    activeSort: { type: String, default: 'newest' },
    isOffer: { type: Boolean, default: false },
});

defineEmits(['selectCategory', 'selectSort']);

const View = resolveThemeComponent({
    warm: WarmCategoryFilter,
    grove: GroveCategoryFilter,
    harbor: HarborCategoryFilter,
});
</script>

<template>
    <component
        :is="View"
        :categories="categories"
        :active-category="activeCategory"
        :active-sort="activeSort"
        :is-offer="isOffer"
        @select-category="$emit('selectCategory', $event)"
        @select-sort="$emit('selectSort', $event)"
    />
</template>
