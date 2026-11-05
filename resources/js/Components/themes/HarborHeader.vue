<script setup>
import { Search, Heart, ShoppingBag, X, User, MapPin } from 'lucide-vue-next';
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
</script>

<template>
    <header class="harbor-header sticky top-0 z-40 bg-white shadow-[0_2px_12px_rgb(15_23_42/0.06)]">
        <OccasionFrame />

        <div class="bg-[#0f172a] text-[#f8fafc] text-[11px] font-semibold uppercase tracking-[0.14em]">
            <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 h-9 flex items-center justify-between gap-3">
                <div class="flex items-center gap-4 overflow-x-auto whitespace-nowrap">
                    <Link href="/track-order" class="inline-flex items-center gap-1.5 hover:text-white">
                        <MapPin class="w-3.5 h-3.5 text-[#93c5fd]" />
                        <span>{{ t('header.track') }}</span>
                    </Link>
                    <Link href="/?offer=1#featured" class="hover:text-white">{{ t('header.offers') }}</Link>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <template v-if="locales.length">
                        <button
                            v-for="loc in locales"
                            :key="loc.code"
                            type="button"
                            class="transition-colors"
                            :class="locale === loc.code ? 'text-white' : 'text-[#94a3b8] hover:text-white'"
                            @click="switchLocale(loc.code)"
                        >
                            {{ loc.short }}
                        </button>
                    </template>
                    <Link :href="page.props.customer ? '/account' : '/login'" class="hidden lg:inline-flex items-center gap-1.5 hover:text-white" :title="page.props.customer ? t('header.account') : t('header.login')">
                        <User class="w-3.5 h-3.5" />
                        <span>{{ page.props.customer ? page.props.customer.name : t('header.login') }}</span>
                    </Link>
                </div>
            </div>
        </div>

        <div class="border-b-4 border-[#1d4ed8]">
            <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 sm:gap-5 py-3">
                    <a href="/" class="shrink-0 flex items-center gap-2.5 min-w-0">
                        <img
                            v-if="page.props.site_settings?.site_logo"
                            :src="page.props.site_settings.site_logo"
                            :alt="page.props.site_settings?.site_name || 'Logo'"
                            class="h-10 w-auto max-w-[150px] object-contain"
                        />
                        <template v-else>
                            <span class="w-10 h-10 bg-[#1d4ed8] text-white flex items-center justify-center rounded-sm shadow-sm shrink-0">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="hidden sm:flex flex-col leading-none min-w-0">
                                <span class="text-lg font-black tracking-tight text-[#0f172a] truncate">{{ page.props.site_settings?.site_name || 'Nobo Shop' }}</span>
                                <span class="text-[10px] uppercase tracking-[0.16em] text-[#64748b] mt-1 truncate">{{ page.props.site_settings?.site_tagline || t('header.tagline_fallback') }}</span>
                            </span>
                        </template>
                    </a>

                    <div ref="desktopSearchContainer" class="flex-1 relative min-w-0">
                        <form class="relative flex" @submit.prevent="handleSearch">
                            <input
                                v-model="searchInput"
                                type="text"
                                class="w-full h-11 pl-3.5 pr-24 bg-[#f4f7fb] border border-[#b9c9de] rounded-sm text-sm text-[#0f172a] placeholder-[#6d83a0] focus:bg-white focus:border-[#1d4ed8] outline-none"
                                :placeholder="t('header.search_placeholder')"
                                @input="onSearchInput"
                                @focus="onSearchFocus"
                            />
                            <button
                                v-if="searchInput"
                                type="button"
                                class="absolute inset-y-0 right-20 px-2 text-[#6d83a0] hover:text-[#1d4ed8]"
                                @click="clearSearch"
                            >
                                <X class="w-4 h-4" />
                            </button>
                            <button type="submit" class="absolute inset-y-0 right-0 w-[4.75rem] bg-[#1d4ed8] hover:bg-[#1e40af] text-white rounded-r-sm flex items-center justify-center font-black">
                                <Search class="w-4 h-4" />
                            </button>
                        </form>
                        <SearchSuggestions
                            tone="harbor"
                            :search-input="searchInput"
                            :suggestions="suggestions"
                            :is-loading="isLoadingSuggestions"
                            :show="showSuggestions"
                            @search="handleSearch"
                            @close="closeSuggestions"
                        />
                    </div>

                    <div class="hidden lg:flex items-center gap-1 sm:gap-2 shrink-0">
                        <button type="button" class="relative z-10 p-2.5 text-[#0f172a] hover:bg-[#f4f7fb] rounded-sm" :title="t('header.wishlist')" @click.stop="openWishlist()">
                            <Heart class="w-5 h-5" />
                            <span v-if="wishlistCount > 0" class="absolute top-1 right-1 min-w-4 h-4 px-1 bg-[#1d4ed8] text-white text-[10px] font-bold rounded-sm flex items-center justify-center">{{ wishlistCount }}</span>
                        </button>
                        <button type="button" class="relative z-10 inline-flex items-center gap-2 px-2.5 sm:px-3 py-2 bg-[#1d4ed8] hover:bg-[#1e40af] text-white rounded-sm" :title="t('header.cart')" @click.stop="openCart()">
                            <ShoppingBag class="w-5 h-5" />
                            <span class="hidden sm:inline text-xs font-bold uppercase tracking-wider">{{ t('header.cart') }}</span>
                            <span v-if="totalCount > 0" class="min-w-5 h-5 px-1 bg-white text-[#1d4ed8] text-[11px] font-black rounded-sm flex items-center justify-center">{{ totalCount }}</span>
                        </button>
                    </div>
                </div>

                <div class="hidden lg:flex items-center gap-1 pb-2 overflow-x-auto">
                    <Link
                        href="/"
                        class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-[#334155] hover:text-[#1d4ed8] hover:bg-[#f4f7fb] rounded-sm"
                    >
                        {{ t('header.home') }}
                    </Link>
                    <Link
                        v-for="cat in categories"
                        :key="cat.id"
                        :href="'/?category=' + cat.slug"
                        class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-[#334155] hover:text-[#1d4ed8] hover:bg-[#f4f7fb] rounded-sm whitespace-nowrap"
                    >
                        {{ cat.display_name || cat.name }}
                    </Link>
                </div>
            </div>
        </div>

        <div ref="mobileSearchContainer" class="hidden" />
    </header>
</template>
