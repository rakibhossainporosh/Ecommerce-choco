<script setup>
import { ref } from 'vue';
import { Search, Heart, ShoppingBag } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { useCart } from '../Composables/useCart';

const props = defineProps({
    searchQuery: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['search']);

const { totalCount, wishlistCount, openCart, openWishlist } = useCart();
const searchInput = ref(props.searchQuery);

const handleSearch = () => {
    emit('search', searchInput.value);
};
</script>

<template>
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-[#EAE3D6] shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-4">
                <!-- Brand / Logo -->
                <a href="/" class="flex items-center gap-3 group shrink-0">
                    <div class="w-11 h-11 rounded-full bg-[#E86A28] flex items-center justify-center text-white shadow-sm transition-transform group-hover:scale-105">
                        <!-- Stylized Nobo Icon matching screenshot -->
                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xl font-bold tracking-tight text-[#1E293B] group-hover:text-[#E86A28] transition-colors leading-tight">
                            Nobo Shop
                        </span>
                        <span class="text-[12px] text-[#64748B] font-medium leading-none mt-0.5">
                            আপনার প্রতিদিনের পছন্দ
                        </span>
                    </div>
                </a>

                <!-- Search Bar -->
                <div class="flex-1 max-w-md mx-4 hidden md:block">
                    <form @submit.prevent="handleSearch" class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                            <Search class="w-4 h-4" />
                        </div>
                        <input
                            v-model="searchInput"
                            type="text"
                            placeholder="পণ্য খুঁজুন"
                            class="w-full pl-10 pr-4 py-2.5 bg-[#F6F1E9] border border-transparent rounded-full text-sm text-[#1E293B] placeholder-[#94A3B8] focus:bg-white focus:border-[#E86A28] focus:ring-2 focus:ring-[#E86A28]/20 transition-all outline-none"
                        />
                    </form>
                </div>

                <!-- Navigation & Action Icons -->
                <div class="flex items-center gap-6 sm:gap-8">
                    <!-- Nav Links -->
                    <nav class="hidden lg:flex items-center gap-6 text-[15px] font-medium text-[#475569]">
                        <Link href="/" class="text-[#1E293B] hover:text-[#E86A28] transition-colors">হোম</Link>
                        <a href="/#categories" class="hover:text-[#E86A28] transition-colors">ক্যাটাগরি</a>
                        <a href="/#featured" class="hover:text-[#E86A28] transition-colors">অফার</a>
                        <Link href="/track-order" class="hover:text-[#E86A28] transition-colors font-bold text-[#E86A28]">ট্র্যাক অর্ডার</Link>
                    </nav>

                    <!-- Icons: Wishlist & Cart -->
                    <div class="flex items-center gap-4">
                        <!-- Wishlist -->
                        <button
                            @click="openWishlist"
                            type="button"
                            class="relative p-2 text-[#475569] hover:text-[#E86A28] transition-colors"
                            title="উইশলিস্ট"
                        >
                            <Heart class="w-6 h-6 stroke-[1.8]" />
                            <span
                                v-if="wishlistCount > 0"
                                class="absolute top-1 right-0 w-4 h-4 bg-[#E86A28] text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-white"
                            >
                                {{ wishlistCount }}
                            </span>
                        </button>

                        <!-- Cart -->
                        <button
                            @click="openCart"
                            type="button"
                            class="relative p-2 text-[#475569] hover:text-[#E86A28] transition-colors"
                            title="কার্ট দেখুন"
                        >
                            <ShoppingBag class="w-6 h-6 stroke-[1.8]" />
                            <span
                                v-if="totalCount > 0"
                                class="absolute top-1 right-0 w-4 h-4 bg-[#E86A28] text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-white"
                            >
                                {{ totalCount }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Search Bar -->
            <div class="pb-3 md:hidden">
                <form @submit.prevent="handleSearch" class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                        <Search class="w-4 h-4" />
                    </div>
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="পণ্য খুঁজুন"
                        class="w-full pl-10 pr-4 py-2 bg-[#F6F1E9] border border-transparent rounded-full text-sm text-[#1E293B] placeholder-[#94A3B8] focus:bg-white focus:border-[#E86A28] outline-none"
                    />
                </form>
            </div>
        </div>
    </header>
</template>
