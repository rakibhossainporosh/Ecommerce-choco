import { computed, onMounted, onUnmounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from './useI18n';

function catalogPath(category) {
    if (category && category !== 'all') {
        return `/category/${category}`;
    }

    return '/';
}

function buildCatalogQuery(filters, page = null) {
    const query = {};

    if (filters.search) {
        query.search = filters.search;
    }

    if (filters.sort && filters.sort !== 'newest') {
        query.sort = filters.sort;
    }

    if (filters.offer) {
        query.offer = 1;
    }

    if (filters.brand) {
        query.brand = filters.brand;
    }

    if (filters.min_price !== null && filters.min_price !== undefined && filters.min_price !== '') {
        query.min_price = filters.min_price;
    }

    if (filters.max_price !== null && filters.max_price !== undefined && filters.max_price !== '') {
        query.max_price = filters.max_price;
    }

    if (filters.in_stock) {
        query.in_stock = 1;
    }

    const pageNumber = page ?? filters.page;
    if (pageNumber && Number(pageNumber) > 1) {
        query.page = Number(pageNumber);
    }

    return query;
}

export function useHomePage(props) {
    const { t } = useI18n();
    const page = usePage();
    const occasion = computed(() => page.props.site_settings?.occasion || null);
    const isCatalogLoading = ref(false);
    let removeStart = null;
    let removeFinish = null;

    onMounted(() => {
        removeStart = router.on('start', (event) => {
            const visitUrl = String(event?.detail?.visit?.url || '');
            if (
                (visitUrl.includes('/') || visitUrl.includes('/category/'))
                && !visitUrl.includes('/product/')
                && !visitUrl.includes('/checkout')
            ) {
                isCatalogLoading.value = true;
            }
        });
        removeFinish = router.on('finish', () => {
            isCatalogLoading.value = false;
        });
    });

    onUnmounted(() => {
        removeStart?.();
        removeFinish?.();
    });

    const productList = computed(() => (Array.isArray(props.products) ? props.products : []));

    const resultTotal = computed(() => {
        const total = props.pagination?.total;

        return typeof total === 'number' ? total : productList.value.length;
    });

    const saleProducts = computed(() => {
        const pinned = Array.isArray(occasion.value?.sale_ids) ? occasion.value.sale_ids : [];
        const byId = new Map(productList.value.map((product) => [product.id, product]));

        if (pinned.length > 0) {
            return pinned
                .map((id) => byId.get(id))
                .filter((product) => product)
                .slice(0, 4);
        }

        return productList.value
            .filter((product) => Number(product.compare_at_price) > Number(product.price))
            .slice(0, 4);
    });

    const showSale = computed(() => Boolean(occasion.value?.sections?.products) && saleProducts.value.length > 0);

    const catalogProducts = computed(() => {
        if (!showSale.value) {
            return productList.value;
        }

        const hidden = new Set(saleProducts.value.map((product) => product.id));

        return productList.value.filter((product) => !hidden.has(product.id));
    });

    const featuredProduct = computed(() => catalogProducts.value[0] || null);
    const remainingProducts = computed(() => catalogProducts.value.slice(1));

    const updateFilters = (newFilters) => {
        const currentFilters = { ...props.filters, ...newFilters };

        if (newFilters.category !== undefined || newFilters.search !== undefined || newFilters.sort !== undefined || newFilters.offer !== undefined) {
            currentFilters.page = 1;
        }

        const isSearchChange = newFilters.search !== undefined;
        const url = catalogPath(currentFilters.category);
        const query = buildCatalogQuery(currentFilters);

        router.get(url, query, {
            preserveState: true,
            preserveScroll: !isSearchChange,
            replace: true,
        });
    };

    const pageUrl = (targetPage) => {
        const url = catalogPath(props.filters?.category);
        const query = buildCatalogQuery(props.filters || {}, targetPage);
        const params = new URLSearchParams();

        Object.entries(query).forEach(([key, value]) => {
            params.set(key, String(value));
        });

        const qs = params.toString();

        return qs ? `${url}?${qs}` : url;
    };

    const handleSearch = (searchQuery) => {
        updateFilters({ search: searchQuery, category: 'all' });
    };

    const handleClearAllFilters = () => {
        updateFilters({
            search: '',
            category: 'all',
            sort: 'newest',
            offer: false,
            brand: '',
            min_price: null,
            max_price: null,
            in_stock: false,
        });
    };

    const handleFacetUpdate = (facets) => {
        updateFilters(facets);
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
        isCatalogLoading,
        saleProducts,
        showSale,
        catalogProducts,
        featuredProduct,
        remainingProducts,
        resultTotal,
        updateFilters,
        pageUrl,
        handleSearch,
        handleClearAllFilters,
        handleSelectCategory,
        handleSelectSort,
        handleFacetUpdate,
        emptyMessage,
    };
}
