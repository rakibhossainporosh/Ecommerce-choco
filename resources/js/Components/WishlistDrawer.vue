<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmWishlistDrawer from './themes/WarmWishlistDrawer.vue';
import GroveWishlistDrawer from './themes/GroveWishlistDrawer.vue';
import HarborWishlistDrawer from './themes/HarborWishlistDrawer.vue';

const View = resolveThemeComponent({
    warm: WarmWishlistDrawer,
    grove: GroveWishlistDrawer,
    harbor: HarborWishlistDrawer,
});
</script>

<template>
    <component :is="View" />
</template>
