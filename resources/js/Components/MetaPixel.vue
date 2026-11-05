<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { setPixelCurrency, trackMeta } from '../Composables/useMetaPixel';
import { useCart } from '../Composables/useCart';

const page = usePage();
const { cartItems, totalAmount, totalCount } = useCart();
const seenEvents = new Set();
let lastPageKey = '';

watch(
    () => page.props?.meta_pixel?.currency,
    (code) => {
        if (typeof code === 'string') {
            setPixelCurrency(code);
        }
    },
    { immediate: true },
);

watch(
    () => page.props?.meta_pixel?.events,
    (events) => {
        if (!Array.isArray(events)) {
            return;
        }

        events.forEach((event) => {
            if (!event?.event_id || seenEvents.has(event.event_id)) {
                return;
            }

            seenEvents.add(event.event_id);
            trackMeta(event.name, event.params || {}, event.event_id);
        });
    },
    { immediate: true },
);

watch(
    () => [page.component, page.url, page.props?.meta_pixel?.id],
    () => {
        if (!page.props?.meta_pixel?.id) {
            return;
        }

        const key = `${page.component}|${page.url}`;

        if (key === lastPageKey) {
            return;
        }

        lastPageKey = key;
        const pixelCurrency = page.props.meta_pixel.currency;

        if (page.component === 'ProductDetail' && page.props.product) {
            const product = page.props.product;

            trackMeta('ViewContent', {
                content_ids: [String(product.id)],
                content_name: product.display_name || product.name,
                content_category: product.category_display_name || product.category_name || undefined,
                content_type: 'product',
                currency: pixelCurrency,
                value: Number(product.price || 0),
            });
        }

        if (page.component === 'Home') {
            const search = page.props.filters?.search;

            if (typeof search === 'string' && search.trim() !== '') {
                trackMeta('Search', {
                    search_string: search.trim(),
                });
            }
        }

        if (page.component === 'Checkout') {
            trackMeta('InitiateCheckout', {
                content_ids: cartItems.value.map((item) => String(item.id)),
                content_type: 'product',
                contents: cartItems.value.map((item) => ({
                    id: String(item.id),
                    quantity: Number(item.quantity || 1),
                    item_price: Number(item.price || 0),
                })),
                currency: pixelCurrency,
                num_items: Number(totalCount.value || 0),
                value: Number(totalAmount.value || 0),
            });
        }
    },
    { immediate: true },
);
</script>

<template></template>
