<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ProductCard from '../ProductCard.vue';
import OccasionCountdown from '../OccasionCountdown.vue';
import { useI18n } from '../../Composables/useI18n';

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});

const { t } = useI18n();
const page = usePage();
const endsAt = computed(() => page.props.site_settings?.occasion?.ends_at || null);
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-[#1E293B]">{{ t('home.campaign_sale') }}</h2>
            <div class="flex flex-wrap items-center gap-3">
                <OccasionCountdown v-if="endsAt" class="sale-countdown" :ends-at="endsAt" />
                <a href="/?offer=1" class="text-sm font-semibold text-[#E86A28] hover:text-[#C2410C]">{{ t('home.campaign_all') }}</a>
            </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <ProductCard
                v-for="product in products"
                :key="product.id"
                :product="product"
            />
        </div>
    </div>
</template>

<style scoped>
.sale-countdown :deep(.countdown-cell) {
    background: #f6f1e9;
    color: #1e293b;
}
</style>
