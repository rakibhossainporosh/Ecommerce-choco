<script setup>
import { ref, watch, useAttrs, onMounted, onUnmounted } from 'vue';
import { Search, Heart, ShoppingBag, ChevronDown, ChevronRight, X, Loader2, User } from 'lucide-vue-next';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useCart } from '../Composables/useCart';

const props = defineProps({
    searchQuery: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['search']);
const attrs = useAttrs();

const { totalCount, wishlistCount, openCart, openWishlist } = useCart();
const page = usePage();

const getInitialSearch = () => {
    if (props.searchQuery) return props.searchQuery;
    if (page.props.filters?.search) return page.props.filters.search;
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        return params.get('search') || '';
    }
    return '';
};

const searchInput = ref(getInitialSearch());
const suggestions = ref([]);
const isLoadingSuggestions = ref(false);
const showSuggestions = ref(false);

const desktopSearchContainer = ref(null);
const mobileSearchContainer = ref(null);

let debounceTimer = null;
let abortController = null;

watch(() => props.searchQuery, (newVal) => {
    searchInput.value = newVal || '';
});

watch(() => page.props.filters?.search, (newVal) => {
    if (newVal !== undefined) {
        searchInput.value = newVal || '';
    }
});

const categories = page.props.global_categories || [];
const showCategoryMenu = ref(false);

const fetchSuggestions = async (query) => {
    if (abortController) {
        abortController.abort();
    }

    const trimmed = query ? query.trim() : '';
    if (!trimmed) {
        suggestions.value = [];
        isLoadingSuggestions.value = false;
        showSuggestions.value = false;
        return;
    }

    isLoadingSuggestions.value = true;
    showSuggestions.value = true;
    abortController = new AbortController();

    try {
        const res = await fetch(`/search/suggestions?q=${encodeURIComponent(trimmed)}`, {
            signal: abortController.signal,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const data = await res.json();
            suggestions.value = data;
        }
    } catch (err) {
        if (err.name !== 'AbortError') {
            console.error('Error fetching suggestions:', err);
        }
    } finally {
        isLoadingSuggestions.value = false;
    }
};

const onSearchInput = () => {
    clearTimeout(debounceTimer);
    const query = searchInput.value ? searchInput.value.trim() : '';

    if (!query) {
        if (abortController) {
            abortController.abort();
        }
        suggestions.value = [];
        isLoadingSuggestions.value = false;
        showSuggestions.value = false;
        return;
    }

    isLoadingSuggestions.value = true;
    showSuggestions.value = true;

    debounceTimer = setTimeout(() => {
        fetchSuggestions(query);
    }, 200);
};

const onSearchFocus = () => {
    const query = searchInput.value ? searchInput.value.trim() : '';
    if (query) {
        showSuggestions.value = true;
        if (suggestions.value.length === 0 && !isLoadingSuggestions.value) {
            fetchSuggestions(query);
        }
    }
};

const closeSuggestions = () => {
    showSuggestions.value = false;
};

const handleSearch = () => {
    closeSuggestions();
    const query = searchInput.value ? searchInput.value.trim() : '';
    emit('search', query);

    const isHome = page.url === '/' || page.url.startsWith('/?');
    if (isHome && attrs.onSearch) {
        return;
    }

    router.get(
        '/',
        query ? { search: query } : {},
        { preserveState: false, preserveScroll: false }
    );
};

const clearSearch = () => {
    searchInput.value = '';
    suggestions.value = [];
    closeSuggestions();
    emit('search', '');

    const isHome = page.url === '/' || page.url.startsWith('/?');
    if (isHome && attrs.onSearch) {
        return;
    }

    router.get('/', {}, { preserveState: false, preserveScroll: false });
};

const handleClickOutside = (event) => {
    const isInsideDesktop = desktopSearchContainer.value && desktopSearchContainer.value.contains(event.target);
    const isInsideMobile = mobileSearchContainer.value && mobileSearchContainer.value.contains(event.target);
    if (!isInsideDesktop && !isInsideMobile) {
        closeSuggestions();
    }
};

const handleKeyDown = (event) => {
    if (event.key === 'Escape') {
        closeSuggestions();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    clearTimeout(debounceTimer);
    if (abortController) {
        abortController.abort();
    }
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});
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
                            {{ page.props.site_settings?.site_name || 'Nobo Shop' }}
                        </span>
                        <span class="text-[12px] text-[#64748B] font-medium leading-none mt-0.5">
                            {{ page.props.site_settings?.site_tagline || 'আপনার প্রতিদিনের পছন্দ' }}
                        </span>
                    </div>
                </a>

                <!-- Search Bar -->
                <div ref="desktopSearchContainer" class="flex-1 max-w-md mx-4 hidden md:block relative">
                    <form @submit.prevent="handleSearch" class="relative flex items-center">
                        <button
                            type="submit"
                            title="অনুসন্ধান করুন"
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#94A3B8] hover:text-[#E86A28] transition-colors cursor-pointer"
                        >
                            <Search class="w-4 h-4" />
                        </button>
                        <input
                            v-model="searchInput"
                            @input="onSearchInput"
                            @focus="onSearchFocus"
                            type="text"
                            placeholder="পণ্য খুঁজুন (যেমন: শার্ট, ঘড়ি, জুতো...)"
                            class="w-full pl-10 pr-9 py-2.5 bg-[#F6F1E9] border border-transparent rounded-full text-sm text-[#1E293B] placeholder-[#94A3B8] focus:bg-white focus:border-[#E86A28] focus:ring-2 focus:ring-[#E86A28]/20 transition-all outline-none"
                        />
                        <button
                            v-if="searchInput"
                            @click="clearSearch"
                            type="button"
                            title="মুছুন"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#94A3B8] hover:text-[#E86A28] transition-colors"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </form>

                    <!-- Desktop Dropdown Suggestions -->
                    <div
                        v-if="showSuggestions && searchInput.trim()"
                        class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-[#EAE3D6] overflow-hidden z-50 transition-all duration-200"
                    >
                        <!-- Loading State -->
                        <div v-if="isLoadingSuggestions" class="py-6 flex items-center justify-center gap-2.5 text-sm text-[#64748B]">
                            <Loader2 class="w-4 h-4 text-[#E86A28] animate-spin" />
                            <span>পণ্য অনুসন্ধান করা হচ্ছে...</span>
                        </div>

                        <!-- Results List -->
                        <div v-else-if="suggestions.length > 0" class="flex flex-col">
                            <div class="px-4 pt-3 pb-1.5 flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-[#94A3B8]">
                                <span>পণ্যসমূহ</span>
                                <span>{{ suggestions.length }} টি পণ্য পাওয়া গেছে</span>
                            </div>
                            <div class="max-h-[380px] overflow-y-auto divide-y divide-[#F6F1E9]">
                                <Link
                                    v-for="item in suggestions"
                                    :key="item.id"
                                    :href="item.url"
                                    @click="closeSuggestions"
                                    class="flex items-center gap-3.5 px-4 py-3 hover:bg-[#FAF7F2] transition-colors group cursor-pointer"
                                >
                                    <img
                                        :src="item.image"
                                        :alt="item.name"
                                        class="w-12 h-12 rounded-xl object-cover bg-[#F6F1E9] border border-[#EAE3D6] shrink-0 group-hover:scale-105 transition-transform"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-[#1E293B] group-hover:text-[#E86A28] transition-colors truncate">
                                            {{ item.name }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs text-[#94A3B8] font-medium">{{ item.category_name }}</span>
                                            <span class="text-xs text-[#CBD5E1]">•</span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-sm font-bold text-[#E86A28]">৳ {{ Number(item.price).toLocaleString('en-US') }}</span>
                                                <span v-if="item.compare_at_price" class="text-xs text-[#94A3B8] line-through">
                                                    ৳ {{ Number(item.compare_at_price).toLocaleString('en-US') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <ChevronRight class="w-4 h-4 text-[#CBD5E1] group-hover:text-[#E86A28] group-hover:translate-x-0.5 transition-all shrink-0" />
                                </Link>
                            </div>

                            <!-- Footer: View all results -->
                            <div class="p-2 bg-[#FDFBF7] border-t border-[#EAE3D6]">
                                <button
                                    @click="handleSearch"
                                    type="button"
                                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-[#E86A28] hover:bg-[#FAF7F2] rounded-xl transition-colors cursor-pointer"
                                >
                                    <span>"{{ searchInput.trim() }}" এর সবগুলো ফলাফল দেখুন</span>
                                    <span class="text-[11px] bg-[#E86A28]/10 text-[#E86A28] px-2 py-0.5 rounded-full font-bold">সব দেখুন ↵</span>
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="py-8 px-4 text-center">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-[#FAF7F2] text-[#94A3B8] flex items-center justify-center">
                                <Search class="w-5 h-5 text-[#94A3B8]" />
                            </div>
                            <p class="text-sm font-medium text-[#64748B]">
                                "{{ searchInput.trim() }}" এর সাথে মিলে এমন কোনো পণ্য পাওয়া যায়নি
                            </p>
                            <p class="text-xs text-[#94A3B8] mt-1">ভিন্ন বানান বা শব্দ দিয়ে আবার চেষ্টা করুন</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation & Action Icons -->
                <div class="flex items-center gap-6 sm:gap-8">
                    <!-- Nav Links -->
                    <nav class="hidden lg:flex items-center gap-6 text-[15px] font-medium text-[#475569]">
                        <Link href="/" class="text-[#1E293B] hover:text-[#E86A28] transition-colors">হোম</Link>
                        
                        <!-- Category Dropdown -->
                        <div class="relative group" @mouseenter="showCategoryMenu = true" @mouseleave="showCategoryMenu = false">
                            <button class="flex items-center gap-1 hover:text-[#E86A28] transition-colors py-2 outline-none">
                                <span>ক্যাটাগরি</span>
                                <ChevronDown class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': showCategoryMenu}" />
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div 
                                class="absolute top-full left-0 w-[240px] bg-white rounded-2xl shadow-xl border border-[#EAE3D6] py-3 mt-1 transform opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 pointer-events-none group-hover:pointer-events-auto z-50"
                            >
                                <div 
                                    v-for="cat in categories" 
                                    :key="cat.id"
                                    class="relative group/sub"
                                >
                                    <Link 
                                        :href="'/?category=' + cat.slug"
                                        class="flex items-center justify-between px-5 py-2.5 text-[#475569] hover:bg-[#FAF7F2] hover:text-[#E86A28] transition-colors text-[15px] w-full text-left"
                                    >
                                        <span>{{ cat.name }}</span>
                                        <ChevronRight v-if="cat.children && cat.children.length > 0" class="w-4 h-4 text-[#94A3B8]" />
                                    </Link>

                                    <!-- Sub-menu -->
                                    <div 
                                        v-if="cat.children && cat.children.length > 0"
                                        class="absolute top-0 left-full ml-1 w-[240px] bg-white rounded-2xl shadow-xl border border-[#EAE3D6] py-3 transform opacity-0 -translate-x-2 group-hover/sub:opacity-100 group-hover/sub:translate-x-0 transition-all duration-300 pointer-events-none group-hover/sub:pointer-events-auto z-50"
                                    >
                                        <div 
                                            v-for="subCat in cat.children" 
                                            :key="subCat.id"
                                            class="relative group/subsub"
                                        >
                                            <Link 
                                                :href="'/?category=' + subCat.slug"
                                                class="flex items-center justify-between px-5 py-2.5 text-[#475569] hover:bg-[#FAF7F2] hover:text-[#E86A28] transition-colors text-[15px] w-full text-left"
                                            >
                                                <span>{{ subCat.name }}</span>
                                                <ChevronRight v-if="subCat.children && subCat.children.length > 0" class="w-4 h-4 text-[#94A3B8]" />
                                            </Link>
                                            
                                            <!-- Sub-sub-menu -->
                                            <div 
                                                v-if="subCat.children && subCat.children.length > 0"
                                                class="absolute top-0 left-full ml-1 w-[240px] bg-white rounded-2xl shadow-xl border border-[#EAE3D6] py-3 transform opacity-0 -translate-x-2 group-hover/subsub:opacity-100 group-hover/subsub:translate-x-0 transition-all duration-300 pointer-events-none group-hover/subsub:pointer-events-auto z-50"
                                            >
                                                <Link 
                                                    v-for="subSubCat in subCat.children" 
                                                    :key="subSubCat.id"
                                                    :href="'/?category=' + subSubCat.slug"
                                                    class="block px-5 py-2.5 text-[#475569] hover:bg-[#FAF7F2] hover:text-[#E86A28] transition-colors text-[15px] w-full text-left"
                                                >
                                                    {{ subSubCat.name }}
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="categories.length === 0" class="px-5 py-2 text-sm text-[#94A3B8]">
                                    কোনো ক্যাটাগরি নেই
                                </div>
                            </div>
                        </div>

                        <Link
                            href="/?offer=1#featured"
                            class="transition-colors"
                            :class="page.props.filters?.offer ? 'text-[#E86A28] font-bold' : 'hover:text-[#E86A28]'"
                        >
                            অফার
                        </Link>
                        <Link href="/track-order" class="hover:text-[#E86A28] transition-colors font-bold text-[#E86A28]">ট্র্যাক অর্ডার</Link>
                    </nav>

                    <!-- Icons: Account, Wishlist & Cart -->
                    <div class="flex items-center gap-2 sm:gap-3">
                        <!-- Customer Account / Login -->
                        <Link
                            v-if="page.props.customer"
                            href="/account"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-[#1E293B] hover:text-[#E86A28] bg-[#FAF7F2] hover:bg-white border border-[#EAE3D6] rounded-xl transition-all shadow-2xs"
                            title="আমার অ্যাকাউন্ট"
                        >
                            <User class="w-4 h-4 text-[#E86A28]" />
                            <span class="hidden sm:inline max-w-[90px] truncate">{{ page.props.customer.name }}</span>
                        </Link>
                        <Link
                            v-else
                            href="/login"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold text-[#1E293B] hover:text-[#E86A28] bg-[#FAF7F2] hover:bg-white border border-[#EAE3D6] rounded-xl transition-all shadow-2xs"
                            title="লগইন করুন"
                        >
                            <User class="w-4 h-4 text-[#E86A28]" />
                            <span class="hidden sm:inline">লগইন</span>
                        </Link>

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
            <div ref="mobileSearchContainer" class="pb-3 md:hidden relative">
                <form @submit.prevent="handleSearch" class="relative flex items-center">
                    <button
                        type="submit"
                        title="অনুসন্ধান করুন"
                        class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#94A3B8] hover:text-[#E86A28] transition-colors cursor-pointer"
                    >
                        <Search class="w-4 h-4" />
                    </button>
                    <input
                        v-model="searchInput"
                        @input="onSearchInput"
                        @focus="onSearchFocus"
                        type="text"
                        placeholder="পণ্য খুঁজুন (যেমন: শার্ট, ঘড়ি, জুতো...)"
                        class="w-full pl-10 pr-9 py-2 bg-[#F6F1E9] border border-transparent rounded-full text-sm text-[#1E293B] placeholder-[#94A3B8] focus:bg-white focus:border-[#E86A28] outline-none"
                    />
                    <button
                        v-if="searchInput"
                        @click="clearSearch"
                        type="button"
                        title="মুছুন"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#94A3B8] hover:text-[#E86A28] transition-colors"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </form>

                <!-- Mobile Dropdown Suggestions -->
                <div
                    v-if="showSuggestions && searchInput.trim()"
                    class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-[#EAE3D6] overflow-hidden z-50 transition-all duration-200"
                >
                    <!-- Loading State -->
                    <div v-if="isLoadingSuggestions" class="py-6 flex items-center justify-center gap-2.5 text-sm text-[#64748B]">
                        <Loader2 class="w-4 h-4 text-[#E86A28] animate-spin" />
                        <span>পণ্য অনুসন্ধান করা হচ্ছে...</span>
                    </div>

                    <!-- Results List -->
                    <div v-else-if="suggestions.length > 0" class="flex flex-col">
                        <div class="px-4 pt-3 pb-1.5 flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-[#94A3B8]">
                            <span>পণ্যসমূহ</span>
                            <span>{{ suggestions.length }} টি পণ্য পাওয়া গেছে</span>
                        </div>
                        <div class="max-h-[60vh] overflow-y-auto divide-y divide-[#F6F1E9]">
                            <Link
                                v-for="item in suggestions"
                                :key="item.id"
                                :href="item.url"
                                @click="closeSuggestions"
                                class="flex items-center gap-3 px-3.5 py-2.5 hover:bg-[#FAF7F2] transition-colors group cursor-pointer"
                            >
                                <img
                                    :src="item.image"
                                    :alt="item.name"
                                    class="w-11 h-11 rounded-xl object-cover bg-[#F6F1E9] border border-[#EAE3D6] shrink-0"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold text-[#1E293B] group-hover:text-[#E86A28] transition-colors truncate">
                                        {{ item.name }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs text-[#94A3B8] font-medium">{{ item.category_name }}</span>
                                        <span class="text-xs text-[#CBD5E1]">•</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-bold text-[#E86A28]">৳ {{ Number(item.price).toLocaleString('en-US') }}</span>
                                            <span v-if="item.compare_at_price" class="text-xs text-[#94A3B8] line-through">
                                                ৳ {{ Number(item.compare_at_price).toLocaleString('en-US') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <ChevronRight class="w-4 h-4 text-[#CBD5E1] group-hover:text-[#E86A28] shrink-0" />
                            </Link>
                        </div>

                        <!-- Footer: View all results -->
                        <div class="p-2 bg-[#FDFBF7] border-t border-[#EAE3D6]">
                            <button
                                @click="handleSearch"
                                type="button"
                                class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-[#E86A28] hover:bg-[#FAF7F2] rounded-xl transition-colors cursor-pointer"
                            >
                                <span>"{{ searchInput.trim() }}" এর সবগুলো ফলাফল দেখুন</span>
                                <span class="text-[11px] bg-[#E86A28]/10 text-[#E86A28] px-2 py-0.5 rounded-full font-bold">সব দেখুন ↵</span>
                            </button>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="py-8 px-4 text-center">
                        <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-[#FAF7F2] text-[#94A3B8] flex items-center justify-center">
                            <Search class="w-5 h-5 text-[#94A3B8]" />
                        </div>
                        <p class="text-sm font-medium text-[#64748B]">
                            "{{ searchInput.trim() }}" এর সাথে মিলে এমন কোনো পণ্য পাওয়া যায়নি
                        </p>
                        <p class="text-xs text-[#94A3B8] mt-1">ভিন্ন বানান বা শব্দ দিয়ে আবার চেষ্টা করুন</p>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
