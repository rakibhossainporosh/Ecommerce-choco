<script setup>
import { computed } from 'vue';
import { useI18n } from '../Composables/useI18n';
import { useStoreTheme } from '../Composables/useStoreTheme';

defineProps({
    video: {
        type: Object,
        default: null,
    },
});

const { t } = useI18n();
const { theme } = useStoreTheme();

const shellClass = computed(() => {
    if (theme.value === 'grove') {
        return 'mt-10 pt-10 border-t border-[#1a2e28]/10';
    }

    if (theme.value === 'harbor') {
        return 'mt-4 bg-white border border-[#d5e0ee] rounded-sm p-5 sm:p-6';
    }

    return 'mt-8 bg-white rounded-3xl p-6 sm:p-10 border border-[#EAE3D6] shadow-sm';
});

const titleClass = computed(() => {
    if (theme.value === 'grove') {
        return 'text-2xl sm:text-3xl font-semibold tracking-tight leading-[1.35] text-[#1a2e28]';
    }

    if (theme.value === 'harbor') {
        return 'text-xs font-black uppercase tracking-[0.14em] text-[#0f2744]';
    }

    return 'text-xl font-bold text-[#1E293B]';
});

const frameClass = computed(() => {
    if (theme.value === 'grove') {
        return 'mt-5 overflow-hidden border border-[#1a2e28]/10 bg-black';
    }

    if (theme.value === 'harbor') {
        return 'mt-3 overflow-hidden rounded-sm border border-[#d5e0ee] bg-black';
    }

    return 'mt-4 overflow-hidden rounded-2xl border border-[#F0EAE1] bg-black';
});
</script>

<template>
    <section v-if="video?.embed_url" :class="shellClass">
        <h2 :class="titleClass" :style="theme === 'grove' ? { fontFamily: 'var(--store-display)' } : undefined">
            {{ t('product.video_heading') }}
        </h2>

        <div :class="frameClass">
            <iframe
                class="aspect-video w-full"
                :src="video.embed_url"
                :title="t('product.video_heading')"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
            />
        </div>
    </section>
</template>
