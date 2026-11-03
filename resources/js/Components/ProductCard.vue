<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmProductCard from './themes/WarmProductCard.vue';
import GroveProductCard from './themes/GroveProductCard.vue';
import HarborProductCard from './themes/HarborProductCard.vue';

defineProps({
    product: {
        type: Object,
        required: true,
    },
    featured: {
        type: Boolean,
        default: false,
    },
});

const View = resolveThemeComponent({
    warm: WarmProductCard,
    grove: GroveProductCard,
    harbor: HarborProductCard,
});
</script>

<template>
    <component :is="View" :product="product" :featured="featured" />
</template>
