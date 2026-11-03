import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from './useI18n';

export function useHomePage(props) {
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

    const featuredProduct = computed(() => catalogProducts.value[0] || null);
    const remainingProducts = computed(() => catalogProducts.value.slice(1));

    const updateFilters = (newFilters) => {
        const currentFilters = { ...props.filters, ...newFilters };

        Object.keys(currentFilters).forEach((key) => {
            if (!currentFilters[key] || currentFilters[key] === 'all' || currentFilters[key] === 'newest') {
                delete currentFilters[key];
            }
        });

        const isSearchChange = newFilters.search !== undefined;

        router.get('/', currentFilters, {
            preserveState: true,
            preserveScroll: !isSearchChange,
            replace: true,
        });
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

    return {
        t,
        page,
        occasion,
        saleProducts,
        showSale,
        catalogProducts,
        featuredProduct,
        remainingProducts,
        updateFilters,
        handleSearch,
        handleClearAllFilters,
        handleSelectCategory,
        handleSelectSort,
        emptyMessage,
    };
}
