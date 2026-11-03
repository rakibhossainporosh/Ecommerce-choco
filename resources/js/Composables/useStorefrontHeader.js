import { ref, watch, useAttrs, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useCart, setCartCustomer } from './useCart';
import { useI18n } from './useI18n';

export function useStorefrontHeader(props, emit) {
    const attrs = useAttrs();
    const { totalCount, wishlistCount, openCart, openWishlist } = useCart();
    const { t, locale, locales, switchLocale } = useI18n();
    const page = usePage();

    watch(() => page.props.customer, (customer) => {
        setCartCustomer(customer);
    }, { immediate: true });

    const getInitialSearch = () => {
        if (props.searchQuery) {
            return props.searchQuery;
        }
        if (page.props.filters?.search) {
            return page.props.filters.search;
        }
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
    const showCategoryMenu = ref(false);
    const categories = page.props.global_categories || [];

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
                suggestions.value = await res.json();
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
            { preserveState: false, preserveScroll: false },
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

    return {
        t,
        locale,
        locales,
        switchLocale,
        page,
        totalCount,
        wishlistCount,
        openCart,
        openWishlist,
        searchInput,
        suggestions,
        isLoadingSuggestions,
        showSuggestions,
        desktopSearchContainer,
        mobileSearchContainer,
        showCategoryMenu,
        categories,
        onSearchInput,
        onSearchFocus,
        closeSuggestions,
        handleSearch,
        clearSearch,
    };
}
