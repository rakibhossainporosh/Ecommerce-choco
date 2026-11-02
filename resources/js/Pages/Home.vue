<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Search, X, Tag } from 'lucide-vue-next';
import Header from '../Components/Header.vue';
import HeroSection from '../Components/HeroSection.vue';
import OccasionSection from '../Components/OccasionSection.vue';
import CampaignSale from '../Components/CampaignSale.vue';
import CategoryFilter from '../Components/CategoryFilter.vue';
import ProductCard from '../Components/ProductCard.vue';
import CartDrawer from '../Components/CartDrawer.vue';
import WishlistDrawer from '../Components/WishlistDrawer.vue';
import Footer from '../Components/Footer.vue';
import { useI18n } from '../Composables/useI18n';
import { computed } from 'vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { t } = useI18n();
const page = usePage();

const occasion = computed(() => page.props.site_settings?.occasion || null);

const saleProducts = computed(() => {
    const pinned = Array.isArray(occasion.value?.sale_ids) ? occasion.value.sale_ids : [];
    const byId = new Map(props.products.map((product) => [product.id, product]));

    if (pinned.length > 0) {
        return pinned
            .map((id) => byId.get(id))
            .filter((product) => product)
            .slice(0, 4);
    }

    return props.products
        .filter((product) => Number(product.compare_at_price) > Number(product.price))
        .slice(0, 4);
});

const showSale = computed(() => Boolean(occasion.value?.sections?.products) && saleProducts.value.length > 0);

const catalogProducts = computed(() => {
    if (!showSale.value) {
        return props.products;
    }

    const hidden = new Set(saleProducts.value.map((product) => product.id));

    return props.products.filter((product) => !hidden.has(product.id));
});

const updateFilters = (newFilters) => {
    const currentFilters = { ...props.filters, ...newFilters };
    
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key] || currentFilters[key] === 'all' || currentFilters[key] === 'newest') {
            delete currentFilters[key];
        }
    });

    const isSearchChange = newFilters.search !== undefined;

    router.get(
        '/',
        currentFilters,
        { preserveState: true, preserveScroll: !isSearchChange, replace: true }
    );
};

const handleSearch = (searchQuery) => {
    updateFilters({ search: searchQuery, category: 'all' });
};

const handleClearAllFilters = () => {
    updateFilters({ search: '', category: 'all', sort: 'newest' });
};

const handleSelectCategory = (categorySlug) => {
    updateFilters({ category: categorySlug });
};

const handleSelectSort = (sortOption) => {
    updateFilters({ sort: sortOption });
};

const emptyMessage = () => {
    if (props.filters.search) {
        return t('home.empty_search', { query: props.filters.search });
    }
    if (props.filters.offer) {
        return t('home.no_offers');
    }
    return t('home.empty_category');
};
</script>

<template>
    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col">
        <!-- Header -->
        <Header 
            :search-query="filters.search || ''" 
            @search="handleSearch" 
        />

        <main>
            <!-- Hero Section -->
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
                <!-- Offer Result Indicator -->
                <div v-if="filters.offer" class="mb-6 flex flex-wrap items-center justify-between gap-3 bg-gradient-to-r from-orange-50 to-amber-50 px-5 py-3.5 rounded-2xl border border-[#FDBA74] shadow-xs">
                    <div class="flex items-center gap-2.5 text-[#C2410C] text-sm">
                        <Tag class="w-4 h-4 text-[#E86A28]" />
                        <span><strong>{{ t('home.offers_banner') }}</strong> {{ t('home.offers_banner_note') }}</span>
                        <span class="text-xs bg-[#E86A28] text-white px-2.5 py-0.5 rounded-full font-bold">
                            {{ t('home.offer_count', { count: products.length }) }}
                        </span>
                    </div>
                    <button 
                        @click="updateFilters({ offer: false })" 
                        type="button"
                        class="text-xs font-bold text-[#E86A28] hover:text-[#C2410C] flex items-center gap-1.5 cursor-pointer bg-white px-3 py-1.5 rounded-xl border border-[#FDBA74] shadow-2xs hover:bg-[#FAF7F2] transition-colors"
                    >
                        <span>{{ t('home.browse_all') }}</span>
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Search Result Indicator -->
                <div v-if="filters.search" class="mb-6 flex flex-wrap items-center justify-between gap-3 bg-white px-5 py-3.5 rounded-2xl border border-[#EAE3D6] shadow-sm">
                    <div class="flex items-center gap-2 text-[#475569] text-sm">
                        <Search class="w-4 h-4 text-[#E86A28]" />
                        <span>{{ t('home.results_for', { query: filters.search }) }}</span>
                        <span class="text-xs bg-[#F6F1E9] text-[#64748B] px-2.5 py-0.5 rounded-full font-semibold">
                            {{ t('home.result_count', { count: products.length }) }}
                        </span>
                    </div>
                    <button 
                        @click="handleSearch('')" 
                        type="button"
                        class="text-xs font-semibold text-[#E86A28] hover:underline flex items-center gap-1 cursor-pointer"
                    >
                        <span>{{ t('home.clear_search') }}</span>
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div v-if="products.length === 0" class="text-center py-20 bg-white rounded-2xl border border-[#EAE3D6] shadow-sm">
                    <p class="text-lg text-[#64748B] font-medium">
                        {{ emptyMessage() }}
                    </p>
                    <button 
                        @click="handleClearAllFilters" 
                        class="mt-4 px-6 py-2 bg-[#E86A28] text-white rounded-full font-medium hover:bg-[#D95F22] transition-colors shadow-sm cursor-pointer"
                    >
                        {{ t('home.browse_all') }}
                    </button>
                </div>
                
                <div v-else-if="catalogProducts.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    <ProductCard
                        v-for="product in catalogProducts"
                        :key="product.id"
                        :product="product"
                    />
                </div>
                </div>
            </OccasionSection>
        </main>

        <!-- Footer -->
        <Footer />

        <!-- Wishlist Drawer -->
        <WishlistDrawer />

        <!-- Cart Drawer -->
        <CartDrawer />
    </div>
</template>
