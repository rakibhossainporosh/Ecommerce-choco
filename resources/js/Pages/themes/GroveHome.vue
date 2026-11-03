<script setup>
import { computed } from 'vue';
import { X, ChevronDown } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import HeroSection from '../../Components/HeroSection.vue';
import CampaignSale from '../../Components/CampaignSale.vue';
import ProductCard from '../../Components/ProductCard.vue';
import Footer from '../../Components/Footer.vue';
import ProductGridSkeleton from '../../Components/ProductGridSkeleton.vue';
import { useHomePage } from '../../Composables/useHomePage';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const {
    t, saleProducts, showSale, catalogProducts, isCatalogLoading,
    updateFilters, handleSearch, handleClearAllFilters,
    handleSelectCategory, handleSelectSort, emptyMessage,
} = useHomePage(props);

const sortOptions = computed(() => [
    { label: t('home.sort_newest'), value: 'newest' },
    { label: t('home.sort_price_asc'), value: 'price_asc' },
    { label: t('home.sort_price_desc'), value: 'price_desc' },
]);

const scrollToLookbook = () => {
    document.getElementById('lookbook')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const shopCategories = computed(() => (props.categories || []).filter((cat) => cat.slug !== 'home'));

const productCount = computed(() => catalogProducts.value.length);

const onSortChange = (event) => {
    handleSelectSort(event.target.value);
};
</script>

<template>
    <div class="min-h-screen bg-[#f3efe8] text-[#1a2e28] flex flex-col">
        <Header :search-query="filters.search || ''" @search="handleSearch" />

        <main class="flex-1">
            <HeroSection @shop-now="scrollToLookbook" />

            <!-- Compact department strip -->
            <section class="border-b border-[#1a2e28]/10 bg-[#efeae2]">
                <div class="max-w-[88rem] mx-auto px-4 sm:px-6 lg:px-8 py-5">
                    <div class="flex items-center gap-2 sm:gap-3 overflow-x-auto pb-0.5">
                        <span class="shrink-0 text-[10px] font-semibold uppercase tracking-[0.22em] text-[#8a9a92] mr-1">{{ t('home.all') }}</span>
                        <button
                            type="button"
                            class="shrink-0 h-9 px-3.5 text-xs font-semibold uppercase tracking-[0.08em] border transition-colors"
                            :class="(!filters.category || filters.category === 'all')
                                ? 'bg-[#1a2e28] border-[#1a2e28] text-white'
                                : 'bg-transparent border-[#1a2e28]/15 text-[#1a2e28] hover:border-[#1a2e28]'"
                            @click="handleSelectCategory('all')"
                        >
                            {{ t('home.all') }}
                        </button>
                        <button
                            v-for="cat in shopCategories"
                            :key="cat.id"
                            type="button"
                            class="shrink-0 h-9 px-3.5 text-xs font-semibold uppercase tracking-[0.08em] border transition-colors whitespace-nowrap"
                            :class="filters.category === cat.slug
                                ? 'bg-[#1a2e28] border-[#1a2e28] text-white'
                                : 'bg-transparent border-[#1a2e28]/15 text-[#1a2e28] hover:border-[#1a2e28]'"
                            @click="handleSelectCategory(cat.slug)"
                        >
                            {{ cat.display_name || cat.name }}
                        </button>
                        <button
                            type="button"
                            class="shrink-0 h-9 px-3.5 text-xs font-semibold uppercase tracking-[0.08em] border transition-colors"
                            :class="filters.offer
                                ? 'bg-[#0e6b58] border-[#0e6b58] text-white'
                                : 'bg-transparent border-[#0e6b58]/40 text-[#0e6b58] hover:border-[#0e6b58]'"
                            @click="updateFilters({ offer: true })"
                        >
                            {{ t('header.offers') }}
                        </button>
                    </div>
                </div>
            </section>

            <CampaignSale v-if="showSale" :products="saleProducts" />

            <section id="lookbook" class="max-w-[88rem] mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 scroll-mt-20">
                <!-- Professional catalog toolbar -->
                <div class="mb-6 pb-4 border-b border-[#1a2e28]/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-end gap-3 min-w-0">
                        <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight leading-[1.4] overflow-visible" style="font-family: var(--store-display)">
                            {{ filters.offer ? t('home.offers_title') : t('home.featured') }}
                        </h2>
                        <span class="shrink-0 pb-1 text-sm text-[#8a9a92] tabular-nums">{{ productCount }}</span>
                    </div>

                    <label class="relative inline-flex items-center gap-2 self-start sm:self-auto">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#8a9a92]">{{ t('home.sort_label') }}</span>
                        <span class="relative">
                            <select
                                class="appearance-none h-9 pl-3 pr-8 bg-white border border-[#1a2e28]/15 text-sm font-medium text-[#1a2e28] outline-none focus:border-[#0e6b58] cursor-pointer min-w-[10.5rem]"
                                :value="filters.sort || 'newest'"
                                @change="onSortChange"
                            >
                                <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <ChevronDown class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[#8a9a92]" />
                        </span>
                    </label>
                </div>

                <div v-if="filters.search" class="mb-5 flex items-center justify-between gap-3 px-4 py-3 bg-white border border-[#1a2e28]/10">
                    <p class="text-sm text-[#5c6f67]">{{ t('home.results_for', { query: filters.search }) }}</p>
                    <button type="button" class="text-sm font-semibold text-[#0e6b58]" @click="handleSearch('')">{{ t('home.clear_search') }}</button>
                </div>

                <div v-if="filters.offer" class="mb-5 flex items-center justify-between gap-3 bg-[#1a2e28] text-[#f3faf6] px-4 py-3">
                    <span class="text-sm font-medium">{{ t('home.offers_banner') }}</span>
                    <button type="button" class="text-sm font-semibold inline-flex items-center gap-1 text-[#9fd6c4]" @click="updateFilters({ offer: false })">
                        {{ t('home.browse_all') }} <X class="w-4 h-4" />
                    </button>
                </div>

                <div v-if="isCatalogLoading" class="py-2">
                    <ProductGridSkeleton :count="8" tone="grove" />
                </div>

                <div v-else-if="products.length === 0" class="text-center py-20 bg-white border border-[#1a2e28]/10">
                    <p class="text-xl font-semibold" style="font-family: var(--store-display)">{{ emptyMessage() }}</p>
                    <button type="button" class="mt-5 h-10 px-5 bg-[#1a2e28] text-white text-xs font-semibold uppercase tracking-[0.12em]" @click="handleClearAllFilters">
                        {{ t('home.browse_all') }}
                    </button>
                </div>

                <div v-else-if="catalogProducts.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-2.5 sm:gap-3 lg:gap-4">
                    <ProductCard v-for="product in catalogProducts" :key="product.id" :product="product" />
                </div>
            </section>
        </main>

        <Footer />
    </div>
</template>
