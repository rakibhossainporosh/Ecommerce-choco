<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmProductDetail from './themes/WarmProductDetail.vue';
import GroveProductDetail from './themes/GroveProductDetail.vue';
import HarborProductDetail from './themes/HarborProductDetail.vue';

defineProps({
    product: {
        type: Object,
        required: true,
    },
    relatedProducts: {
        type: Array,
        default: () => [],
    },
});

const View = resolveThemeComponent({
    warm: WarmProductDetail,
    grove: GroveProductDetail,
    harbor: HarborProductDetail,
});
</script>

<template>
    <component :is="View" :product="product" :related-products="relatedProducts" />
</template>
