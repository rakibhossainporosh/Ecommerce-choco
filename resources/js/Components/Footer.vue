<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmFooter from './themes/WarmFooter.vue';
import GroveFooter from './themes/GroveFooter.vue';
import HarborFooter from './themes/HarborFooter.vue';

const View = resolveThemeComponent({
    warm: WarmFooter,
    grove: GroveFooter,
    harbor: HarborFooter,
});
</script>

<template>
    <component :is="View" />
</template>
