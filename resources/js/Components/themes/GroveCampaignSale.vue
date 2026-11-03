<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ProductCard from '../ProductCard.vue';
import OccasionCountdown from '../OccasionCountdown.vue';
import { useI18n } from '../../Composables/useI18n';

defineProps({
    products: { type: Array, default: () => [] },
});

const { t } = useI18n();
const page = usePage();
const endsAt = computed(() => page.props.site_settings?.occasion?.ends_at || null);
const occasionLabel = computed(() => page.props.site_settings?.occasion?.label || t('home.campaign_sale'));
</script>

<template>
    <!-- Full-bleed dark campaign band -->
    <div class="bg-[#1a2e28] text-[#f3faf6]">
        <div class="max-w-[88rem] mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <div class="mb-10 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.28em] text-[#9fd6c4] font-semibold">{{ occasionLabel }}</p>
                    <h2 class="mt-2 text-3xl sm:text-4xl font-semibold tracking-tight" style="font-family: var(--store-display)">
                        {{ t('home.campaign_sale') }}
                    </h2>
                </div>
                <div class="flex flex-wrap items-center gap-5">
                    <OccasionCountdown v-if="endsAt" class="sale-countdown" :ends-at="endsAt" />
                    <a href="/?offer=1" class="text-[11px] font-semibold uppercase tracking-[0.16em] border-b border-[#9fd6c4] pb-1 text-[#9fd6c4]">
                        {{ t('home.campaign_all') }}
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-3 lg:gap-4">
                <ProductCard v-for="product in products" :key="product.id" :product="product" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.sale-countdown :deep(.countdown-cell) {
    background: rgb(255 255 255 / 0.08);
    color: #f3faf6;
}

/* Lookbook cards on dark band need readable text */
:deep(h3) {
    color: #f3faf6;
}
:deep(.store-price) {
    color: #9fd6c4;
}
:deep(p) {
    color: #b7d0c6;
}
</style>
