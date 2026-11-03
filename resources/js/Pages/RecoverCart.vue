<script setup>
import { onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Header from '../Components/Header.vue';
import Footer from '../Components/Footer.vue';
import { useI18n } from '../Composables/useI18n';
import { useStorefrontPageShell } from '../Composables/useStorefrontPageShell';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const { t } = useI18n();
const { pageClass, primaryButtonClass, inkClass, mutedClass } = useStorefrontPageShell();

onMounted(() => {
    if (!props.items.length) {
        return;
    }

    const cart = props.items.map((item) => ({
        cartItemId: `${item.product_id}-${item.variant_id}`,
        id: item.product_id,
        variant_id: item.variant_id,
        name: item.name,
        price: Number(item.unit_price),
        quantity: Number(item.quantity),
        image: null,
    }));

    localStorage.setItem('nobo_cart', JSON.stringify(cart));
    window.location.href = '/checkout';
});
</script>

<template>
    <Head :title="t('cart.title')" />

    <div :class="pageClass">
        <Header />

        <main class="flex-1 max-w-xl mx-auto px-4 py-20 text-center w-full">
            <p v-if="items.length" class="text-lg font-semibold" :class="inkClass">{{ t('cart.recovering') }}</p>
            <template v-else>
                <p class="text-lg font-semibold" :class="inkClass">{{ t('cart.recover_empty') }}</p>
                <p class="mt-2 text-sm" :class="mutedClass">{{ t('checkout.empty_hint') }}</p>
                <Link href="/" :class="[primaryButtonClass, 'inline-flex mt-6 px-5 py-3']">
                    {{ t('cart.recover_home') }}
                </Link>
            </template>
        </main>

        <Footer />
    </div>
</template>
