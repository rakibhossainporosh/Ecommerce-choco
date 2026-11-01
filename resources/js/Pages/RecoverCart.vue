<script setup>
import { onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Header from '../Components/Header.vue';
import Footer from '../Components/Footer.vue';
import { useI18n } from '../Composables/useI18n';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const { t } = useI18n();

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

    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col">
        <Header />

        <main class="flex-1 max-w-xl mx-auto px-4 py-20 text-center w-full">
            <p v-if="items.length" class="text-lg font-semibold">{{ t('cart.recovering') }}</p>
            <template v-else>
                <p class="text-lg font-semibold">{{ t('cart.recover_empty') }}</p>
                <Link href="/" class="inline-flex mt-6 bg-[#E86A28] text-white font-semibold px-5 py-3 rounded-xl">
                    {{ t('cart.recover_home') }}
                </Link>
            </template>
        </main>

        <Footer />
    </div>
</template>
