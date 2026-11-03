<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmCampaignSale from './themes/WarmCampaignSale.vue';
import GroveCampaignSale from './themes/GroveCampaignSale.vue';
import HarborCampaignSale from './themes/HarborCampaignSale.vue';

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});

const View = resolveThemeComponent({
    warm: WarmCampaignSale,
    grove: GroveCampaignSale,
    harbor: HarborCampaignSale,
});
</script>

<template>
    <component :is="View" :products="products" />
</template>
