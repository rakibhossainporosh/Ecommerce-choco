<script setup>
import { computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, ShoppingBag, ArrowRight, Truck } from 'lucide-vue-next';
import Header from '../Components/Header.vue';
import Footer from '../Components/Footer.vue';
import { useCart } from '../Composables/useCart';
import { useI18n } from '../Composables/useI18n';
import { useStorefrontPageShell } from '../Composables/useStorefrontPageShell';

const props = defineProps({
    order_number: {
        type: String,
        default: null
    },
    payment: {
        type: Object,
        default: null
    }
});

const { t } = useI18n();
const { clearCart } = useCart();
const { pageClass, panelClass, primaryButtonClass, accentTextClass, inkClass, mutedClass } = useStorefrontPageShell();

const noticeClass = computed(() => {
    if (props.payment?.mode === 'manual' || (props.payment?.mode === 'redirect' && !props.payment?.paid)) {
        return 'bg-[#FFF7F2] border border-[#F0EAE1] text-[#9A3412]';
    }

    return 'bg-[#F0FDF4] border border-[#BBF7D0] text-[#166534]';
});

onMounted(() => {
    if (props.order_number) {
        clearCart();
    }
});
</script>

<template>
    <Head :title="t('meta.success')" />

    <div :class="pageClass">
        <Header />

        <main class="flex-1 flex items-center justify-center p-4 sm:p-8">
            <div :class="[panelClass, 'max-w-xl w-full p-8 md:p-12 text-center']">
                
                <div class="inline-flex items-center justify-center w-24 h-24 bg-[#ECFDF5] mb-8 shadow-inner">
                    <CheckCircle class="w-12 h-12 text-[#10B981]" />
                </div>

                <h1 class="text-3xl md:text-4xl font-bold mb-4" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('success.title') }}</h1>
                <p class="text-lg mb-8" :class="mutedClass">
                    {{ t('success.body') }}
                </p>

                <div v-if="order_number" :class="[panelClass, 'p-6 mb-8 inline-block min-w-[250px]']">
                    <span class="block text-sm font-semibold mb-1" :class="mutedClass">{{ t('success.order_number') }}</span>
                    <span class="block text-2xl font-bold" :class="accentTextClass">{{ order_number }}</span>
                </div>

                <div class="rounded-xl p-5 mb-10 text-sm md:text-base font-medium text-left" :class="noticeClass">
                    <div class="flex items-start gap-3">
                        <ShoppingBag class="w-5 h-5 shrink-0 mt-0.5" />
                        <div class="min-w-0">
                            <p class="font-bold break-words">{{ payment?.title || t('success.cod') }}</p>
                            <p class="mt-1 break-words">{{ payment?.body || t('success.cod') }}</p>
                            <p v-if="payment?.account" class="mt-3 text-lg font-bold tracking-wide break-all">{{ payment.account }}</p>
                            <p v-if="payment?.reference" class="mt-2 break-all">{{ t('success.manual_reference') }}: {{ payment.reference }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link 
                        v-if="order_number"
                        :href="'/track-order?order_number=' + order_number"
                        class="w-full sm:w-auto px-8 py-3.5 bg-white border-2 font-bold text-base transition-all flex items-center justify-center gap-2"
                        :class="accentTextClass"
                        style="border-color: currentColor"
                    >
                        <Truck class="w-4 h-4" />
                        <span>{{ t('success.track') }}</span>
                    </Link>

                    <Link 
                        href="/" 
                        :class="[primaryButtonClass, 'w-full sm:w-auto px-8 py-3.5 text-sm']"
                    >
                        <span>{{ t('success.continue') }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                </div>

            </div>
        </main>
        
        <Footer />
    </div>
</template>
