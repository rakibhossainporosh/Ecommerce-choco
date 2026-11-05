<script setup>
import { computed } from 'vue';
import { X, Tag, Search, Truck, ShieldCheck, RotateCcw, Zap } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import HeroSection from '../../Components/HeroSection.vue';
import CampaignSale from '../../Components/CampaignSale.vue';
import ProductCard from '../../Components/ProductCard.vue';
import Footer from '../../Components/Footer.vue';
import ProductGridSkeleton from '../../Components/ProductGridSkeleton.vue';
import CatalogPager from '../../Components/CatalogPager.vue';
import { useHomePage } from '../../Composables/useHomePage';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    pagination: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const {
    t, saleProducts, showSale, catalogProducts, isCatalogLoading,
    updateFilters, pageUrl, handleSearch, handleClearAllFilters,
    handleSelectCategory, handleSelectSort, emptyMessage,
} = useHomePage(props);

const sortOptions = computed(() => [
    { label: t('home.sort_newest'), value: 'newest' },
    { label: t('home.sort_price_asc'), value: 'price_asc' },
    { label: t('home.sort_price_desc'), value: 'price_desc' },
]);

const scrollToFeatured = () => {
    document.getElementById('featured')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>

<template>
    <div class="min-h-screen bg-[#e8eef5] text-[#0f172a] flex flex-col">
        <Header :search-query="filters.search || ''" @search="handleSearch" />

        <main class="flex-1">
            <div class="max-w-[90rem] mx-auto px-2 sm:px-4 lg:px-6 py-3">
                <div class="flex gap-3 items-start">
                    <aside class="hidden lg:block w-56 shrink-0 sticky top-[11rem] z-20">
                        <div class="bg-white border border-[#c5d0e0] rounded-sm overflow-hidden shadow-[0_8px_24px_rgb(15_23_42/0.06)]">
                            <div class="px-3 py-2.5 bg-[#1d4ed8] text-white text-[11px] font-black uppercase tracking-[0.12em] flex items-center gap-2">
                                <Zap class="w-3.5 h-3.5 text-[#93c5fd]" />
                                {{ t('home.all') }}
                            </div>
                            <nav class="py-1 max-h-[70vh] overflow-y-auto">
                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2.5 text-sm border-l-4 transition-colors"
                                    :class="(!filters.category || filters.category === 'all') ? 'border-[#1d4ed8] bg-[#eff6ff] font-bold text-[#1d4ed8]' : 'border-transparent text-[#334155] hover:bg-[#f8fafc]'"
                                    @click="handleSelectCategory('all')"
                                >
                                    {{ t('home.all') }}
                                </button>
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    type="button"
                                    class="w-full text-left px-3 py-2.5 text-sm border-l-4 transition-colors"
                                    :class="filters.category === cat.slug ? 'border-[#1d4ed8] bg-[#eff6ff] font-bold text-[#1d4ed8]' : 'border-transparent text-[#334155] hover:bg-[#f8fafc]'"
                                    @click="handleSelectCategory(cat.slug)"
                                >
                                    {{ cat.display_name || cat.name }}
                                </button>
                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2.5 text-sm border-l-4 border-transparent text-[#1d4ed8] font-bold hover:bg-[#eff6ff]"
                                    @click="updateFilters({ offer: true })"
                                >
                                    {{ t('header.offers') }}
                                </button>
                            </nav>
                        </div>
                    </aside>

                    <div class="flex-1 min-w-0 space-y-3">
                        <HeroSection @shop-now="scrollToFeatured" />

                        <div class="grid grid-cols-3 gap-2">
                            <div class="bg-white border border-[#c5d0e0] rounded-sm px-2.5 py-2 flex items-center gap-2 shadow-sm">
                                <Truck class="w-4 h-4 text-[#1d4ed8] shrink-0" />
                                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-[#334155] truncate">{{ t('product.perk_fast_delivery') }}</span>
                            </div>
                            <div class="bg-white border border-[#c5d0e0] rounded-sm px-2.5 py-2 flex items-center gap-2 shadow-sm">
                                <ShieldCheck class="w-4 h-4 text-[#1d4ed8] shrink-0" />
                                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-[#334155] truncate">{{ t('product.perk_cod') }}</span>
                            </div>
                            <div class="bg-white border border-[#c5d0e0] rounded-sm px-2.5 py-2 flex items-center gap-2 shadow-sm">
                                <RotateCcw class="w-4 h-4 text-[#1d4ed8] shrink-0" />
                                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-[#334155] truncate">{{ t('product.perk_easy_return') }}</span>
                            </div>
                        </div>

                        <div class="bg-[#eff6ff] border border-[#93c5fd] rounded-sm px-3 py-2.5 flex flex-wrap items-center justify-between gap-2 shadow-sm">
                            <div class="flex items-center gap-2 text-sm text-[#1e40af] font-bold">
                                <Tag class="w-4 h-4 text-[#1d4ed8]" />
                                <span class="uppercase tracking-wider text-[11px]">{{ t('home.campaign_sale') }}</span>
                                <span class="hidden sm:inline font-semibold text-[#1d4ed8]">{{ t('home.offers_banner_note') }}</span>
                            </div>
                            <a href="/?offer=1" class="text-[11px] font-black uppercase tracking-wider text-[#1d4ed8] hover:text-[#1e40af]">{{ t('home.campaign_all') }} →</a>
                        </div>

                        <CampaignSale v-if="showSale" :products="saleProducts" />

                        <section id="featured" class="bg-white border border-[#c5d0e0] rounded-sm shadow-[0_8px_24px_rgb(15_23_42/0.05)] scroll-mt-40 lg:scroll-mt-36">
                            <div class="px-3 py-3 border-b border-[#e2e8f0] flex flex-col sm:flex-row sm:items-center gap-2 justify-between bg-gradient-to-r from-[#f8fafc] to-white">
                                <h2 class="text-[11px] font-black uppercase tracking-[0.14em] text-[#0f172a]">
                                    {{ filters.offer ? t('home.offers_title') : t('home.featured') }}
                                </h2>
                                <div class="flex items-center gap-2 overflow-x-auto">
                                    <button
                                        v-for="opt in sortOptions"
                                        :key="opt.value"
                                        type="button"
                                        class="h-8 px-2.5 text-[10px] font-black uppercase tracking-wider rounded-sm border whitespace-nowrap transition-colors"
                                        :class="(filters.sort || 'newest') === opt.value ? 'bg-[#1d4ed8] border-[#1d4ed8] text-white' : 'bg-white border-[#cbd5e1] text-[#475569] hover:border-[#1d4ed8]'"
                                        @click="handleSelectSort(opt.value)"
                                    >
                                        {{ opt.label }}
                                    </button>
                                </div>
                            </div>

                            <div class="lg:hidden px-3 py-2 border-b border-[#e2e8f0] flex gap-1.5 overflow-x-auto">
                                <button
                                    type="button"
                                    class="h-8 px-2.5 text-[10px] font-black uppercase tracking-wider rounded-sm border whitespace-nowrap"
                                    :class="(!filters.category || filters.category === 'all') ? 'bg-[#1d4ed8] border-[#1d4ed8] text-white' : 'bg-[#f8fafc] border-[#cbd5e1]'"
                                    @click="handleSelectCategory('all')"
                                >{{ t('home.all') }}</button>
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    type="button"
                                    class="h-8 px-2.5 text-[10px] font-black uppercase tracking-wider rounded-sm border whitespace-nowrap"
                                    :class="filters.category === cat.slug ? 'bg-[#1d4ed8] border-[#1d4ed8] text-white' : 'bg-[#f8fafc] border-[#cbd5e1]'"
                                    @click="handleSelectCategory(cat.slug)"
                                >{{ cat.display_name || cat.name }}</button>
                            </div>

                            <div class="p-3">
                                <div v-if="filters.search" class="mb-3 flex items-center justify-between gap-2 text-sm bg-[#f8fafc] border border-[#e2e8f0] rounded-sm px-3 py-2">
                                    <span class="inline-flex items-center gap-2 text-[#475569]"><Search class="w-3.5 h-3.5" />{{ t('home.results_for', { query: filters.search }) }}</span>
                                    <button type="button" class="text-[#1d4ed8] font-bold text-xs" @click="handleSearch('')">{{ t('home.clear_search') }}</button>
                                </div>

                                <div v-if="filters.offer" class="mb-3 flex items-center justify-between gap-2 text-sm bg-[#eff6ff] border border-[#93c5fd] rounded-sm px-3 py-2">
                                    <span class="font-bold text-[#1e40af]">{{ t('home.offers_banner') }}</span>
                                    <button type="button" class="text-[#1d4ed8] font-black text-[10px] uppercase inline-flex items-center gap-1" @click="updateFilters({ offer: false })">
                                        {{ t('home.browse_all') }} <X class="w-3 h-3" />
                                    </button>
                                </div>

                                <div v-if="isCatalogLoading" class="py-1">
                                    <ProductGridSkeleton :count="8" tone="harbor" />
                                </div>

                                <div v-else-if="products.length === 0" class="text-center py-16">
                                    <p class="font-bold">{{ emptyMessage() }}</p>
                                    <button type="button" class="mt-4 h-10 px-5 bg-[#1d4ed8] text-white text-xs font-black uppercase tracking-wider rounded-sm" @click="handleClearAllFilters">{{ t('home.browse_all') }}</button>
                                </div>

                                <div v-else-if="catalogProducts.length" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2.5 sm:gap-3">
                                    <ProductCard v-for="product in catalogProducts" :key="product.id" :product="product" />
                                </div>

                                <CatalogPager :pagination="pagination" :build-url="pageUrl" />
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
