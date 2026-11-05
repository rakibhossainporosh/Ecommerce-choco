<script setup>
import { Search, X, Tag } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import HeroSection from '../../Components/HeroSection.vue';
import OccasionSection from '../../Components/OccasionSection.vue';
import CampaignSale from '../../Components/CampaignSale.vue';
import CategoryFilter from '../../Components/CategoryFilter.vue';
import ProductCard from '../../Components/ProductCard.vue';
import ProductGridSkeleton from '../../Components/ProductGridSkeleton.vue';
    import CatalogPager from '../../Components/CatalogPager.vue';
    import Footer from '../../Components/Footer.vue';
    import { useHomePage } from '../../Composables/useHomePage';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    pagination: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const {
    t, saleProducts, showSale, catalogProducts, isCatalogLoading, resultTotal,
    updateFilters, pageUrl, handleSearch, handleClearAllFilters,
    handleSelectCategory, handleSelectSort, emptyMessage,
} = useHomePage(props);
</script>

<template>
    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col">
        <Header :search-query="filters.search || ''" @search="handleSearch" />

        <main>
            <OccasionSection part="hero">
                <HeroSection />
            </OccasionSection>

            <OccasionSection part="categories">
                <CategoryFilter
                    :categories="categories"
                    :active-category="filters.category || 'all'"
                    :active-sort="filters.sort || 'newest'"
                    :is-offer="!!filters.offer"
                    @select-category="handleSelectCategory"
                    @select-sort="handleSelectSort"
                />
            </OccasionSection>

            <OccasionSection part="products">
                <CampaignSale v-if="showSale" :products="saleProducts" />
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
                    <div v-if="filters.offer" class="mb-6 flex flex-wrap items-center justify-between gap-3 bg-gradient-to-r from-orange-50 to-amber-50 px-5 py-3.5 rounded-2xl border border-[#FDBA74] shadow-xs">
                        <div class="flex items-center gap-2.5 text-[#C2410C] text-sm">
                            <Tag class="w-4 h-4 text-[#E86A28]" />
                            <span><strong>{{ t('home.offers_banner') }}</strong> {{ t('home.offers_banner_note') }}</span>
                            <span class="text-xs bg-[#E86A28] text-white px-2.5 py-0.5 rounded-full font-bold">
                                {{ t('home.offer_count', { count: resultTotal }) }}
                            </span>
                        </div>
                        <button type="button" class="text-xs font-bold text-[#E86A28] hover:text-[#C2410C] flex items-center gap-1.5 cursor-pointer bg-white px-3 py-1.5 rounded-xl border border-[#FDBA74]" @click="updateFilters({ offer: false })">
                            <span>{{ t('home.browse_all') }}</span>
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div v-if="filters.search" class="mb-6 flex flex-wrap items-center justify-between gap-3 bg-white px-5 py-3.5 rounded-2xl border border-[#EAE3D6] shadow-sm">
                        <div class="flex items-center gap-2 text-[#475569] text-sm">
                            <Search class="w-4 h-4 text-[#E86A28]" />
                            <span>{{ t('home.results_for', { query: filters.search }) }}</span>
                            <span class="text-xs bg-[#F6F1E9] text-[#64748B] px-2.5 py-0.5 rounded-full font-semibold">
                                {{ t('home.result_count', { count: resultTotal }) }}
                            </span>
                        </div>
                        <button type="button" class="text-xs font-semibold text-[#E86A28] hover:underline flex items-center gap-1 cursor-pointer" @click="handleSearch('')">
                            <span>{{ t('home.clear_search') }}</span>
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div v-if="isCatalogLoading" class="py-1">
                        <ProductGridSkeleton :count="8" tone="warm" />
                    </div>

                    <div v-else-if="products.length === 0" class="text-center py-20 bg-white rounded-2xl border border-[#EAE3D6] shadow-sm">
                        <p class="text-lg text-[#64748B] font-medium">{{ emptyMessage() }}</p>
                        <button type="button" class="mt-4 px-6 py-2 bg-[#E86A28] text-white rounded-full font-medium hover:bg-[#D95F22]" @click="handleClearAllFilters">
                            {{ t('home.browse_all') }}
                        </button>
                    </div>

                    <div v-else-if="catalogProducts.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                        <ProductCard v-for="product in catalogProducts" :key="product.id" :product="product" />
                    </div>

                    <CatalogPager :pagination="pagination" :build-url="pageUrl" />
                </div>
            </OccasionSection>
        </main>

        <Footer />
    </div>
</template>
