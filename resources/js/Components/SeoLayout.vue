<script setup>
import MetaPixel from './MetaPixel.vue';
import SeoHead from './SeoHead.vue';
import WhatsAppFloat from './WhatsAppFloat.vue';
</script>

<template>
    <SeoHead />
    <MetaPixel />
    <slot />
    <WhatsAppFloat />
</template>
