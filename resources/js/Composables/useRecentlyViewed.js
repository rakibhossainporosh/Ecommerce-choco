import { ref, onMounted } from 'vue';

const STORAGE_KEY = 'nobo_recently_viewed';
const MAX_ITEMS = 8;

const readStore = () => {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        return raw ? JSON.parse(raw) : [];
    } catch {
        return [];
    }
};

const writeStore = (items) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items.slice(0, MAX_ITEMS)));
};

export function useRecentlyViewed() {
    const items = ref([]);

    const track = (product) => {
        if (!product?.id || !product?.slug) {
            return;
        }

        const next = readStore().filter((item) => item.id !== product.id);
        next.unshift({
            id: product.id,
            slug: product.slug,
            name: product.display_name || product.name,
            display_name: product.display_name || product.name,
            image: product.image,
            fallback_image: product.fallback_image,
            price: product.price,
            compare_at_price: product.compare_at_price,
            category_name: product.category_display_name || product.category_name,
            category_display_name: product.category_display_name || product.category_name,
            is_in_stock: product.is_in_stock,
            default_variant_id: product.default_variant_id,
        });
        writeStore(next);
        items.value = next;
    };

    const othersThan = (productId) => items.value.filter((item) => item.id !== productId).slice(0, 4);

    onMounted(() => {
        items.value = readStore();
    });

    return {
        items,
        track,
        othersThan,
    };
}
