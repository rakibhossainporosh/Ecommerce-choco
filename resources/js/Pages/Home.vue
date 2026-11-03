<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmHome from './themes/WarmHome.vue';
import GroveHome from './themes/GroveHome.vue';
import HarborHome from './themes/HarborHome.vue';

defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const View = resolveThemeComponent({
    warm: WarmHome,
    grove: GroveHome,
    harbor: HarborHome,
});
</script>

<template>
    <component
        :is="View"
        :categories="categories"
        :products="products"
        :filters="filters"
    />
</template>
