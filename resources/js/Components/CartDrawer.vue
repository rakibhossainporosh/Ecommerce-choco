<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmCartDrawer from './themes/WarmCartDrawer.vue';
import GroveCartDrawer from './themes/GroveCartDrawer.vue';
import HarborCartDrawer from './themes/HarborCartDrawer.vue';

const View = resolveThemeComponent({
    warm: WarmCartDrawer,
    grove: GroveCartDrawer,
    harbor: HarborCartDrawer,
});
</script>

<template>
    <component :is="View" />
</template>
