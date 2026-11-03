<script setup>
import { resolveThemeComponent } from '../Composables/useStoreTheme';
import WarmHeroSection from './themes/WarmHeroSection.vue';
import GroveHeroSection from './themes/GroveHeroSection.vue';
import HarborHeroSection from './themes/HarborHeroSection.vue';

defineEmits(['shopNow']);

const View = resolveThemeComponent({
    warm: WarmHeroSection,
    grove: GroveHeroSection,
    harbor: HarborHeroSection,
});
</script>

<template>
    <component :is="View" @shop-now="$emit('shopNow')" />
</template>
