<script setup>
import { computed } from 'vue';
import { Search, Heart, ShoppingBag, X, User } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import OccasionFrame from '../OccasionFrame.vue';
import SearchSuggestions from './SearchSuggestions.vue';
import { useStorefrontHeader } from '../../Composables/useStorefrontHeader';

const props = defineProps({
    searchQuery: { type: String, default: '' },
});

const emit = defineEmits(['search']);

const {
    t, locale, locales, switchLocale, page,
    totalCount, wishlistCount, openCart, openWishlist,
    searchInput, suggestions, isLoadingSuggestions, showSuggestions,
    desktopSearchContainer, mobileSearchContainer, categories,
    onSearchInput, onSearchFocus, closeSuggestions, handleSearch, clearSearch,
} = useStorefrontHeader(props, emit);

const navCategories = computed(() => (categories || []).filter((cat) => cat.slug !== 'home').slice(0, 5));
</script>

<template>
    <!-- Dark editorial masthead — opposite of Warm's cream chrome -->
    <header class="grove-header sticky top-0 z-40 bg-[#1a2e28] text-[#f3faf6]">
        <OccasionFrame />

        <div class="max-w-[88rem] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4 h-14 sm:h-[3.75rem]">
                <a href="/" class="shrink-0 flex items-center gap-2.5 min-w-0">
                    <img
                        v-if="page.props.site_settings?.site_logo"
                        :src="page.props.site_settings.site_logo"
                        :alt="page.props.site_settings?.site_name || 'Logo'"
                        class="h-9 sm:h-10 w-auto max-w-[140px] object-contain"
                    />
                    <span
                        v-else
                        class="text-lg sm:text-xl font-semibold tracking-tight truncate"
                        style="font-family: var(--store-display)"
                    >
                        {{ page.props.site_settings?.site_name || 'Nobo Shop' }}
                    </span>
                </a>

                <nav class="hidden md:flex items-center gap-5 ml-4">
                    <Link
                        v-for="cat in navCategories"
                        :key="cat.id"
                        :href="'/?category=' + cat.slug"
                        class="text-[12px] font-medium uppercase tracking-[0.14em] text-[#b7d0c6] hover:text-white transition-colors whitespace-nowrap"
                    >
                        {{ cat.display_name || cat.name }}
                    </Link>
                    <Link href="/?offer=1#lookbook" class="text-[12px] font-medium uppercase tracking-[0.14em] text-[#9fd6c4] hover:text-white whitespace-nowrap">
                        {{ t('header.offers') }}
                    </Link>
                </nav>

                <div ref="desktopSearchContainer" class="flex-1 relative min-w-0 max-w-sm ml-auto">
                    <form class="relative" @submit.prevent="handleSearch">
                        <button type="submit" class="absolute inset-y-0 left-0 pl-3 flex items-center text-[#5c6f67] hover:text-[#1a2e28]">
                            <Search class="w-4 h-4" />
                        </button>
                        <input
                            v-model="searchInput"
                            type="text"
                            class="w-full h-8 pl-9 pr-8 bg-[#f3efe8] border border-transparent text-[13px] text-[#1a2e28] placeholder-[#8a9a92] focus:border-[#9fd6c4] focus:ring-1 focus:ring-[#9fd6c4]/40 outline-none"
                            :placeholder="t('header.search_placeholder')"
                            @input="onSearchInput"
                            @focus="onSearchFocus"
                        />
                        <button v-if="searchInput" type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#8a9a92] hover:text-[#1a2e28]" @click="clearSearch">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </form>
                    <SearchSuggestions
                        tone="grove"
                        :search-input="searchInput"
                        :suggestions="suggestions"
                        :is-loading="isLoadingSuggestions"
                        :show="showSuggestions"
                        @search="handleSearch"
                        @close="closeSuggestions"
                    />
                </div>

                <div class="flex items-center gap-0.5 shrink-0">
                    <div v-if="locales.length" class="hidden sm:flex items-center gap-1 mr-1 text-[10px] font-semibold uppercase tracking-wider">
                        <button
                            v-for="loc in locales"
                            :key="loc.code"
                            type="button"
                            class="px-1 py-1 transition-colors"
                            :class="locale === loc.code ? 'text-white' : 'text-[#8eaea3] hover:text-white'"
                            @click="switchLocale(loc.code)"
                        >
                            {{ loc.short }}
                        </button>
                    </div>
                    <Link :href="page.props.customer ? '/account' : '/login'" class="p-2 text-[#b7d0c6] hover:text-white" :title="page.props.customer ? t('header.account') : t('header.login')">
                        <User class="w-5 h-5" />
                    </Link>
                    <button type="button" class="relative z-10 p-2 text-[#b7d0c6] hover:text-white" :title="t('header.wishlist')" @click.stop="openWishlist()">
                        <Heart class="w-5 h-5" />
                        <span v-if="wishlistCount > 0" class="absolute top-1 right-0.5 min-w-4 h-4 px-1 bg-[#9fd6c4] text-[#1a2e28] text-[10px] font-bold rounded-full flex items-center justify-center">{{ wishlistCount }}</span>
                    </button>
                    <button type="button" class="relative z-10 p-2 text-[#b7d0c6] hover:text-white" :title="t('header.cart')" @click.stop="openCart()">
                        <ShoppingBag class="w-5 h-5" />
                        <span v-if="totalCount > 0" class="absolute top-1 right-0.5 min-w-4 h-4 px-1 bg-[#9fd6c4] text-[#1a2e28] text-[10px] font-bold rounded-full flex items-center justify-center">{{ totalCount }}</span>
                    </button>
                </div>
            </div>

            <nav class="md:hidden flex gap-4 pb-3 overflow-x-auto border-t border-white/10 pt-2">
                <Link
                    v-for="cat in navCategories"
                    :key="cat.id"
                    :href="'/?category=' + cat.slug"
                    class="text-[11px] font-medium uppercase tracking-[0.14em] text-[#b7d0c6] whitespace-nowrap"
                >{{ cat.display_name || cat.name }}</Link>
                <Link href="/?offer=1#lookbook" class="text-[11px] font-medium uppercase tracking-[0.14em] text-[#9fd6c4] whitespace-nowrap">{{ t('header.offers') }}</Link>
            </nav>

            <div ref="mobileSearchContainer" class="hidden" />
        </div>
    </header>
</template>
