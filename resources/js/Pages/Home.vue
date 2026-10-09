<script setup>
import { router } from '@inertiajs/vue3';
import Header from '../Components/Header.vue';
import HeroSection from '../Components/HeroSection.vue';
import CategoryFilter from '../Components/CategoryFilter.vue';
import ProductCard from '../Components/ProductCard.vue';
import CartDrawer from '../Components/CartDrawer.vue';
import Footer from '../Components/Footer.vue';

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

const updateFilters = (newFilters) => {
    // Preserve existing filters, override with new ones
    const currentFilters = { ...props.filters, ...newFilters };
    
    // Remove empty parameters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key] || currentFilters[key] === 'all' || currentFilters[key] === 'newest') {
            delete currentFilters[key];
        }
    });

    router.get(
        '/',
        currentFilters,
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const handleSearch = (searchQuery) => {
    updateFilters({ search: searchQuery });
};

const handleSelectCategory = (categorySlug) => {
    updateFilters({ category: categorySlug });
};

const handleSelectSort = (sortOption) => {
    updateFilters({ sort: sortOption });
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
            <HeroSection />

            <!-- Category Filter & Sort -->
            <CategoryFilter
                :categories="categories"
                :active-category="filters.category || 'all'"
                :active-sort="filters.sort || 'newest'"
                @select-category="handleSelectCategory"
                @select-sort="handleSelectSort"
            />

            <!-- Product Grid -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
                <div v-if="products.length === 0" class="text-center py-20 bg-white rounded-2xl border border-[#EAE3D6] shadow-sm">
                    <p class="text-lg text-[#64748B] font-medium">দুঃখিত, এই ক্যাটাগরিতে কোনো পণ্য পাওয়া যায়নি।</p>
                    <button 
                        @click="handleSelectCategory('all')" 
                        class="mt-4 px-6 py-2 bg-[#E86A28] text-white rounded-full font-medium hover:bg-[#D95F22] transition-colors shadow-sm"
                    >
                        সব পণ্য দেখুন
                    </button>
                </div>
                
                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    <ProductCard
                        v-for="product in products"
                        :key="product.id"
                        :product="product"
                    />
                </div>
            </div>
        </main>

        <!-- Footer -->
        <Footer />

        <!-- Cart Drawer -->
        <CartDrawer />
    </div>
</template>
