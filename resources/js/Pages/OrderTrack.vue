<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Package, MapPin, Truck, CheckCircle2, ChevronRight, AlertCircle, ShoppingBag } from 'lucide-vue-next';
import Header from '../Components/Header.vue';
import Footer from '../Components/Footer.vue';
import { useI18n } from '../Composables/useI18n';
import { useStorefrontPageShell } from '../Composables/useStorefrontPageShell';

const props = defineProps({
    order: {
        type: Object,
        default: null
    },
    searched_order_number: {
        type: String,
        default: ''
    },
    searched_phone: {
        type: String,
        default: ''
    },
    error: {
        type: String,
        default: null
    }
});

const { t } = useI18n();
const { pageClass, panelClass, fieldClass, primaryButtonClass, accentTextClass, inkClass, mutedClass } = useStorefrontPageShell();

const orderNumber = ref(props.searched_order_number || '');
const phone = ref(props.searched_phone || '');
const isSearching = ref(false);

const handleSearch = () => {
    if (!orderNumber.value) return;
    
    isSearching.value = true;
    router.get('/track-order', {
        order_number: orderNumber.value,
        phone: phone.value
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isSearching.value = false
    });
};

const formatPrice = (val) => {
    return '৳ ' + Number(val).toLocaleString('en-US');
};

const getStatusColor = (status) => {
    const statusMap = {
        'pending': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'confirmed': 'bg-blue-100 text-blue-800 border-blue-200',
        'processing': 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'shipped': 'bg-purple-100 text-purple-800 border-purple-200',
        'delivered': 'bg-green-100 text-green-800 border-green-200',
        'cancelled': 'bg-red-100 text-red-800 border-red-200',
        'returned': 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return statusMap[status?.toLowerCase()] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusLabel = (status) => {
    const key = status?.toLowerCase();
    const map = {
        pending: t('track.status_pending'),
        confirmed: t('track.status_confirmed'),
        processing: t('track.status_processing'),
        shipped: t('track.status_shipped'),
        delivered: t('track.status_delivered'),
        cancelled: t('track.status_cancelled'),
        returned: t('track.status_returned'),
    };
    return map[key] || status;
};

const steps = computed(() => [
    { key: 'pending', label: t('track.step_placed'), icon: ShoppingBag },
    { key: 'confirmed', label: t('track.step_confirmed'), icon: CheckCircle2 },
    { key: 'processing', label: t('track.step_packaging'), icon: Package },
    { key: 'shipped', label: t('track.step_shipping'), icon: Truck },
    { key: 'delivered', label: t('track.step_delivered'), icon: MapPin },
]);

const getCurrentStepIndex = () => {
    if (!props.order) return -1;
    const currentStatus = props.order.status.toLowerCase();
    
    if (currentStatus === 'cancelled' || currentStatus === 'returned') return -1;
    
    const index = steps.value.findIndex(s => s.key === currentStatus);
    return index !== -1 ? index : 0;
};
</script>

<template>
    <Head :title="t('meta.track')" />

    <div :class="pageClass">
        <Header />

        <main class="flex-1 max-w-4xl mx-auto px-4 sm:px-6 py-8 w-full flex flex-col">
            <nav class="flex items-center gap-2 text-sm mb-8" :class="mutedClass">
                <Link href="/" class="hover:opacity-80 transition-opacity" :class="accentTextClass">{{ t('header.home') }}</Link>
                <ChevronRight class="w-4 h-4" />
                <span class="font-medium" :class="inkClass">{{ t('track.breadcrumb') }}</span>
            </nav>

            <div class="flex-1 flex flex-col justify-center" :class="{ 'my-auto': !order }">
                <div :class="[panelClass, 'p-6 md:p-8 mb-8']">
                <div class="text-center mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold mb-2" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('track.title') }}</h1>
                    <p :class="mutedClass">{{ t('track.intro') }}</p>
                </div>

                <form class="max-w-2xl mx-auto flex flex-col md:flex-row gap-4" @submit.prevent="handleSearch">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-[#94A3B8]">
                            <Search class="w-5 h-5" />
                        </div>
                        <input
                            v-model="orderNumber"
                            type="text"
                            required
                            :placeholder="t('track.order_number_placeholder')"
                            :class="[fieldClass, 'pl-11 pr-4 py-3.5 text-[15px]']"
                        />
                    </div>
                    <div class="md:w-1/3">
                        <input
                            v-model="phone"
                            type="text"
                            :placeholder="t('track.phone')"
                            :class="[fieldClass, 'px-4 py-3.5 text-[15px]']"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="isSearching"
                        :class="[primaryButtonClass, 'px-8 py-3.5 whitespace-nowrap']"
                    >
                        {{ isSearching ? t('track.searching') : t('track.submit') }}
                    </button>
                </form>

                <div v-if="error" class="mt-6 max-w-2xl mx-auto p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3 text-red-600">
                    <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
                    <span class="text-sm font-medium">{{ error }}</span>
                </div>
            </div>
            </div>

            <div v-if="order" class="space-y-6">
                <div :class="[panelClass, 'p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6']">
                    <div>
                        <h2 class="text-xl font-bold mb-2" :class="inkClass">{{ t('track.order_label', { number: order.order_number }) }}</h2>
                        <div class="flex items-center gap-3 text-sm" :class="mutedClass">
                            <span>{{ order.placed_at }}</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-current opacity-30"></span>
                            <span>{{ t('track.item_count', { count: order.items.length }) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="px-4 py-1.5 rounded-full text-sm font-bold border" :class="getStatusColor(order.status)">
                            {{ getStatusLabel(order.status) }}
                        </span>
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-medium" :class="mutedClass">{{ t('track.grand_total') }}</div>
                            <div class="text-xl font-bold" :class="accentTextClass">{{ formatPrice(order.grand_total) }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="getCurrentStepIndex() !== -1" :class="[panelClass, 'p-6 md:p-8 overflow-x-auto']">
                    <div class="min-w-[600px]">
                        <div class="flex justify-between relative">
                            <div class="absolute left-8 right-8 top-6 h-1 bg-black/5 -z-10 rounded-full"></div>
                            <div
                                class="absolute left-8 top-6 h-1 -z-10 rounded-full transition-all duration-500"
                                :class="accentTextClass"
                                :style="{ width: `calc(${(getCurrentStepIndex() / (steps.length - 1)) * 100}% - 4rem)`, backgroundColor: 'currentColor' }"
                            ></div>

                            <div v-for="(step, index) in steps" :key="step.key" class="flex flex-col items-center w-24">
                                <div
                                    class="w-12 h-12 rounded-full flex items-center justify-center mb-3 transition-colors duration-300 border-2 bg-white"
                                    :class="index <= getCurrentStepIndex() ? accentTextClass : 'border-[#94A3B8]/40 text-[#94A3B8]'"
                                    :style="index <= getCurrentStepIndex() ? { borderColor: 'currentColor' } : undefined"
                                >
                                    <component :is="step.icon" class="w-5 h-5" />
                                </div>
                                <span
                                    class="text-xs font-bold text-center"
                                    :class="index <= getCurrentStepIndex() ? inkClass : 'text-[#94A3B8]'"
                                >
                                    {{ step.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else :class="[panelClass, 'p-6 flex items-center gap-4']">
                    <AlertCircle class="w-8 h-8 text-rose-500 shrink-0" />
                    <div>
                        <h3 class="text-lg font-bold" :class="inkClass">{{ t('track.order_cancelled_title', { status: getStatusLabel(order.status) }) }}</h3>
                        <p class="text-sm mt-1" :class="mutedClass">{{ t('track.contact_support') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div :class="[panelClass, 'md:col-span-2 p-6']">
                        <h3 class="font-bold mb-4 border-b border-black/5 pb-3" :class="inkClass">{{ t('track.ordered_items') }}</h3>
                        <div class="divide-y divide-black/5">
                            <div v-for="(item, index) in order.items" :key="index" class="py-3 flex justify-between items-center">
                                <div>
                                    <div class="font-semibold text-sm" :class="inkClass">{{ item.name }}</div>
                                    <div class="text-xs mt-1" :class="mutedClass">{{ formatPrice(item.price) }} x {{ item.quantity }}</div>
                                </div>
                                <div class="font-bold" :class="inkClass">{{ formatPrice(item.total) }}</div>
                            </div>
                        </div>
                    </div>

                    <div :class="[panelClass, 'p-6 space-y-6']">
                        <div>
                            <h3 class="font-bold mb-3 text-sm uppercase tracking-wider" :class="mutedClass">{{ t('track.address') }}</h3>
                            <div class="text-sm font-medium" :class="inkClass">{{ order.customer_name }}</div>
                            <div class="text-sm mt-1" :class="mutedClass">{{ order.customer_phone }}</div>
                            <div class="text-sm mt-1" :class="mutedClass">{{ order.shipping_address }}</div>
                        </div>

                        <div class="border-t border-black/5 pt-6">
                            <h3 class="font-bold mb-3 text-sm uppercase tracking-wider" :class="mutedClass">{{ t('track.payment') }}</h3>
                            <div class="flex justify-between text-sm mb-2">
                                <span :class="mutedClass">{{ t('track.payment_method') }}</span>
                                <span class="font-medium capitalize" :class="inkClass">{{ order.payment_method }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span :class="mutedClass">{{ t('track.payment_status') }}</span>
                                <span class="font-medium px-2 py-0.5 rounded text-xs"
                                    :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                >
                                    {{ order.payment_status === 'paid' ? t('track.paid') : t('track.unpaid') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
        
        <Footer />
    </div>
</template>
