<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Flame } from 'lucide-vue-next';
import ProductCard from '../ProductCard.vue';
import OccasionCountdown from '../OccasionCountdown.vue';
import { useI18n } from '../../Composables/useI18n';

defineProps({
    products: { type: Array, default: () => [] },
});

const { t } = useI18n();
const page = usePage();
const endsAt = computed(() => page.props.site_settings?.occasion?.ends_at || null);
</script>

<template>
    <div class="max-w-[90rem] mx-auto px-0 pb-1">
        <div class="bg-[#0f172a] border border-[#1e293b] rounded-sm px-4 py-3 mb-3 flex flex-col lg:flex-row lg:items-center justify-between gap-3 shadow-sm">
            <h2 class="text-xs font-black uppercase tracking-[0.14em] text-white inline-flex items-center gap-2">
                <Flame class="w-4 h-4 text-[#93c5fd]" />
                {{ t('home.campaign_sale') }}
            </h2>
            <div class="flex flex-wrap items-center gap-3">
                <OccasionCountdown v-if="endsAt" class="sale-countdown" :ends-at="endsAt" />
                <a href="/?offer=1" class="text-[10px] font-black uppercase tracking-[0.1em] text-[#93c5fd] hover:text-white">{{ t('home.campaign_all') }}</a>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5 sm:gap-3">
            <ProductCard v-for="product in products" :key="product.id" :product="product" />
        </div>
    </div>
</template>

<style scoped>
.sale-countdown :deep(.countdown-cell) {
    background: #1e293b;
    color: #f8fafc;
}
</style>
