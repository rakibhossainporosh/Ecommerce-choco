<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search, X, Tag } from 'lucide-vue-next';
import Header from '../Components/Header.vue';
import HeroSection from '../Components/HeroSection.vue';
import CategoryFilter from '../Components/CategoryFilter.vue';
import ProductCard from '../Components/ProductCard.vue';
import CartDrawer from '../Components/CartDrawer.vue';
import WishlistDrawer from '../Components/WishlistDrawer.vue';
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

    const isSearchChange = newFilters.search !== undefined;

    router.get(
        '/',
        currentFilters,
        { preserveState: true, preserveScroll: !isSearchChange, replace: true }
    );
};

const handleSearch = (searchQuery) => {
    // Reset category to 'all' on new search so search checks across all categories
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
                :is-offer="!!filters.offer"
                @select-category="handleSelectCategory"
                @select-sort="handleSelectSort"
            />

            <!-- Product Grid -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
                <!-- Offer Result Indicator -->
                <div v-if="filters.offer" class="mb-6 flex flex-wrap items-center justify-between gap-3 bg-gradient-to-r from-orange-50 to-amber-50 px-5 py-3.5 rounded-2xl border border-[#FDBA74] shadow-xs">
                    <div class="flex items-center gap-2.5 text-[#C2410C] text-sm">
                        <Tag class="w-4 h-4 text-[#E86A28]" />
                        <span><strong>বিশেষ অফারের পণ্যসমূহ</strong> (ডিসকাউন্ট ও ছাড়ের পণ্য)</span>
                        <span class="text-xs bg-[#E86A28] text-white px-2.5 py-0.5 rounded-full font-bold">
                            {{ products.length }} টি অফার
                        </span>
                    </div>
                    <button 
                        @click="updateFilters({ offer: false })" 
                        type="button"
                        class="text-xs font-bold text-[#E86A28] hover:text-[#C2410C] flex items-center gap-1.5 cursor-pointer bg-white px-3 py-1.5 rounded-xl border border-[#FDBA74] shadow-2xs hover:bg-[#FAF7F2] transition-colors"
                    >
                        <span>সকল পণ্য দেখুন</span>
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Search Result Indicator -->
                <div v-if="filters.search" class="mb-6 flex flex-wrap items-center justify-between gap-3 bg-white px-5 py-3.5 rounded-2xl border border-[#EAE3D6] shadow-sm">
                    <div class="flex items-center gap-2 text-[#475569] text-sm">
                        <Search class="w-4 h-4 text-[#E86A28]" />
                        <span>"<strong>{{ filters.search }}</strong>" এর অনুসন্ধান ফলাফল</span>
                        <span class="text-xs bg-[#F6F1E9] text-[#64748B] px-2.5 py-0.5 rounded-full font-semibold">
                            {{ products.length }} টি পণ্য
                        </span>
                    </div>
                    <button 
                        @click="handleSearch('')" 
                        type="button"
                        class="text-xs font-semibold text-[#E86A28] hover:underline flex items-center gap-1 cursor-pointer"
                    >
                        <span>অনুসন্ধান মুছুন</span>
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div v-if="products.length === 0" class="text-center py-20 bg-white rounded-2xl border border-[#EAE3D6] shadow-sm">
                    <p class="text-lg text-[#64748B] font-medium">
                        {{ filters.search ? `দুঃখিত, "${filters.search}" এর সাথে মিলে এমন কোনো পণ্য পাওয়া যায়নি।` : (filters.offer ? 'বর্তমানে কোনো বিশেষ অফার নেই।' : 'দুঃখিত, এই ক্যাটাগরিতে কোনো পণ্য পাওয়া যায়নি।') }}
                    </p>
                    <button 
                        @click="handleClearAllFilters" 
                        class="mt-4 px-6 py-2 bg-[#E86A28] text-white rounded-full font-medium hover:bg-[#D95F22] transition-colors shadow-sm cursor-pointer"
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

        <!-- Wishlist Drawer -->
        <WishlistDrawer />

        <!-- Cart Drawer -->
        <CartDrawer />
    </div>
</template>
