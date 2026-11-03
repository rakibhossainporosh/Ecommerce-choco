<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmHeader from './themes/WarmHeader.vue';
import GroveHeader from './themes/GroveHeader.vue';
import HarborHeader from './themes/HarborHeader.vue';
import CartToast from './CartToast.vue';

defineProps({
    searchQuery: {
        type: String,
        default: '',
    },
});

defineEmits(['search']);

const View = resolveThemeComponent({
    warm: WarmHeader,
    grove: GroveHeader,
    harbor: HarborHeader,
});
</script>

<template>
    <component :is="View" :search-query="searchQuery" @search="$emit('search', $event)" />
    <CartToast />
</template>
