<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import {
    Package, ShoppingBag, LogOut, Phone, Mail, Calendar,
    Truck, ChevronRight, CheckCircle2, Edit3, RotateCcw, X, Bell, MapPin, FileText
} from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';
import { useStorefrontPageShell } from '../../Composables/useStorefrontPageShell';

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    },
    orders: {
        type: Array,
        default: () => [],
    },
    return_requests: {
        type: Array,
        default: () => [],
    },
    return_window_days: {
        type: Number,
        default: 3,
    },
    notifications: {
        type: Array,
        default: () => [],
    },
    addresses: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ total_orders: 0, total_spent: 0 }),
    },
});

const page = usePage();
const { t } = useI18n();
const {
    pageClass, panelClass, primaryButtonClass, accentTextClass, inkClass, mutedClass, fieldClass,
    softPanelClass, tabActiveClass, tabIdleClass,
} = useStorefrontPageShell();

const tabFromUrl = page.url.includes('tab=returns')
    ? 'returns'
    : (page.url.includes('tab=notifications')
        ? 'notifications'
        : (page.url.includes('tab=addresses') ? 'addresses' : 'orders'));
const activeTab = ref(tabFromUrl);
const unreadNotifications = computed(() => props.notifications.filter((n) => !n.read).length);
const returnOrder = ref(null);

const profileForm = useForm({
    name: props.customer.name,
    email: props.customer.email || '',
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const addressForm = useForm({
    name: props.customer.name,
    phone: props.customer.phone,
    address_line: '',
    area: '',
    city: 'Dhaka',
    postcode: '',
    is_default: false,
});

const returnForm = useForm({
    reason: '',
    items: [],
});

const saveAddress = () => {
    addressForm.post('/account/addresses', {
        preserveScroll: true,
        onSuccess: () => {
            addressForm.reset('address_line', 'area', 'postcode');
            addressForm.is_default = false;
            activeTab.value = 'addresses';
        },
    });
};

const deleteAddress = (address) => {
    router.delete(`/account/addresses/${address.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = 'addresses';
        },
    });
};

const makeDefaultAddress = (address) => {
    router.post(`/account/addresses/${address.id}/default`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = 'addresses';
        },
    });
};

const updateProfile = () => {
    profileForm.post('/account/profile', {
        preserveScroll: true,
        onSuccess: () => {
            profileForm.reset('current_password', 'new_password', 'new_password_confirmation');
        },
    });
};

const handleLogout = () => {
    router.post('/logout');
};

const formatPrice = (val) => {
    return '৳ ' + Number(val).toLocaleString('en-US');
};

const getStatusBadge = (status) => {
    const badges = {
        pending: { text: t('account.status_pending'), class: 'bg-amber-100 text-amber-800' },
        confirmed: { text: t('account.status_confirmed'), class: 'bg-blue-100 text-blue-800' },
        processing: { text: t('account.status_processing'), class: 'bg-indigo-100 text-indigo-800' },
        shipped: { text: t('account.status_shipped'), class: 'bg-purple-100 text-purple-800' },
        delivered: { text: t('account.status_delivered'), class: 'bg-emerald-100 text-emerald-800' },
        returned: { text: t('account.status_returned'), class: 'bg-slate-200 text-slate-800' },
        refunded: { text: t('account.status_refunded'), class: 'bg-slate-200 text-slate-800' },
        approved: { text: t('account.status_confirmed'), class: 'bg-blue-100 text-blue-800' },
        received: { text: t('account.return_status_received'), class: 'bg-indigo-100 text-indigo-800' },
        completed: { text: t('account.status_delivered'), class: 'bg-emerald-100 text-emerald-800' },
        rejected: { text: t('account.status_cancelled'), class: 'bg-rose-100 text-rose-800' },
        cancelled: { text: t('account.status_cancelled'), class: 'bg-slate-200 text-slate-700' },
    };
    return badges[status] || { text: status, class: 'bg-gray-100 text-gray-800' };
};

const cancelReturn = (ret) => {
    if (!ret?.can_cancel) {
        return;
    }

    router.delete(`/account/returns/${ret.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = 'returns';
        },
    });
};

const markNotificationRead = (notification) => {
    if (!notification?.id || notification.read) {
        return;
    }

    router.post(`/account/notifications/${notification.id}/read`, { tab: 'notifications' }, {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = 'notifications';
        },
    });
};

const markAllNotificationsRead = () => {
    router.post('/account/notifications/read-all', {}, {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = 'notifications';
        },
    });
};

const paymentLabel = (method) => {
    return method === 'cod' ? t('account.payment_cod') : method;
};

const openReturnModal = (order) => {
    returnOrder.value = order;
    returnForm.reason = '';
    returnForm.items = (order.returnable_items || order.items || [])
        .filter((item) => Number(item.remaining_quantity ?? item.quantity) > 0)
        .map((item) => {
            const max = Number(item.remaining_quantity ?? item.quantity);

            return {
                order_item_id: item.id,
                quantity: max,
                selected: true,
                max,
                name: item.name,
            };
        });
    returnForm.clearErrors();
};

const closeReturnModal = () => {
    returnOrder.value = null;
    returnForm.reset();
};

const selectedReturnItems = computed(() =>
    returnForm.items.filter((item) => item.selected && item.quantity > 0)
);

const submitReturn = () => {
    if (!returnOrder.value) {
        return;
    }

    returnForm
        .transform((data) => ({
            reason: data.reason,
            items: selectedReturnItems.value.map((item) => ({
                order_item_id: item.order_item_id,
                quantity: Number(item.quantity),
            })),
        }))
        .post(`/account/orders/${returnOrder.value.id}/returns`, {
            preserveScroll: true,
            onSuccess: () => {
                closeReturnModal();
                activeTab.value = 'returns';
            },
        });
};
</script>

<template>
    <Head :title="t('meta.account')" />

    <div :class="[pageClass, 'justify-between']">
        <Header />

        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            <nav class="flex items-center gap-2 text-sm mb-6" :class="mutedClass">
                <Link href="/" class="hover:opacity-80 transition-opacity" :class="accentTextClass">{{ t('header.home') }}</Link>
                <ChevronRight class="w-4 h-4" />
                <span class="font-medium" :class="inkClass">{{ t('account.title') }}</span>
            </nav>

            <div v-if="page.props.flash?.success" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <span class="text-sm font-semibold">{{ page.props.flash.success }}</span>
            </div>

            <div v-if="page.props.flash?.error" class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 flex items-center gap-3">
                <X class="w-5 h-5 text-rose-600 shrink-0" />
                <span class="text-sm font-semibold">{{ page.props.flash.error }}</span>
            </div>

            <div :class="[panelClass, 'p-6 sm:p-8 mb-8']">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 border font-bold text-2xl flex items-center justify-center shrink-0" :class="[accentTextClass, panelClass]">
                            {{ customer.name ? customer.name.charAt(0) : t('reviews.satisfied_customer').charAt(0) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ customer.name }}</h1>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-sm" :class="mutedClass">
                                <span class="flex items-center gap-1.5">
                                    <Phone class="w-3.5 h-3.5 opacity-60" />
                                    {{ customer.phone }}
                                </span>
                                <span v-if="customer.email" class="flex items-center gap-1.5">
                                    <Mail class="w-3.5 h-3.5 opacity-60" />
                                    {{ customer.email }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <Calendar class="w-3.5 h-3.5 opacity-60" />
                                    {{ t('account.member_since', { date: customer.member_since }) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <button
                            @click="handleLogout"
                            type="button"
                            class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-xl text-sm font-semibold flex items-center gap-2 transition-colors cursor-pointer"
                        >
                            <LogOut class="w-4 h-4" />
                            <span>{{ t('account.logout') }}</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-black/5">
                    <div :class="[softPanelClass, 'p-4']">
                        <span class="text-xs font-semibold block mb-1" :class="mutedClass">{{ t('account.total_orders') }}</span>
                        <span class="text-2xl font-bold" :class="inkClass">{{ stats.total_orders }}</span>
                    </div>
                    <div :class="[softPanelClass, 'p-4']">
                        <span class="text-xs font-semibold block mb-1" :class="mutedClass">{{ t('account.total_spent') }}</span>
                        <span class="text-2xl font-bold" :class="accentTextClass">{{ formatPrice(stats.total_spent) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 mb-6 border-b border-black/10 pb-2">
                <button
                    @click="activeTab = 'orders'"
                    type="button"
                    class="px-5 py-2.5 text-sm font-bold flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'orders' ? tabActiveClass : tabIdleClass"
                >
                    <Package class="w-4 h-4" />
                    <span>{{ t('account.orders_tab', { count: orders.length }) }}</span>
                </button>

                <button
                    @click="activeTab = 'returns'"
                    type="button"
                    class="px-5 py-2.5 text-sm font-bold flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'returns' ? tabActiveClass : tabIdleClass"
                >
                    <RotateCcw class="w-4 h-4" />
                    <span>{{ t('account.returns_tab', { count: return_requests.length }) }}</span>
                </button>

                <button
                    @click="activeTab = 'notifications'"
                    type="button"
                    class="px-5 py-2.5 text-sm font-bold flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'notifications' ? tabActiveClass : tabIdleClass"
                >
                    <Bell class="w-4 h-4" />
                    <span>{{ t('account.notifications_tab', { count: unreadNotifications }) }}</span>
                </button>

                <button
                    @click="activeTab = 'addresses'"
                    type="button"
                    class="px-5 py-2.5 text-sm font-bold flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'addresses' ? tabActiveClass : tabIdleClass"
                >
                    <MapPin class="w-4 h-4" />
                    <span>{{ t('account.addresses_tab', { count: addresses.length }) }}</span>
                </button>

                <button
                    @click="activeTab = 'profile'"
                    type="button"
                    class="px-5 py-2.5 text-sm font-bold flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'profile' ? tabActiveClass : tabIdleClass"
                >
                    <Edit3 class="w-4 h-4" />
                    <span>{{ t('account.profile_settings') }}</span>
                </button>
            </div>

            <div v-if="activeTab === 'orders'" class="space-y-6">
                <div
                    v-for="order in orders"
                    :key="order.id"
                    :class="[panelClass, 'p-6 sm:p-8 transition-all']"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-black/5">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-bold" :class="inkClass">{{ t('account.order') }} #{{ order.order_number }}</h3>
                                <span
                                    class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                    :class="getStatusBadge(order.status).class"
                                >
                                    {{ getStatusBadge(order.status).text }}
                                </span>
                            </div>
                            <span class="text-xs mt-1 block" :class="mutedClass">
                                {{ t('account.order_date', { date: order.created_at }) }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-lg font-bold" :class="inkClass">
                                {{ formatPrice(order.grand_total) }}
                            </span>
                            <a
                                :href="order.invoice_url || ('/account/orders/' + order.id + '/invoice')"
                                target="_blank"
                                rel="noopener noreferrer"
                                :class="[softPanelClass, 'px-4 py-2 text-xs font-semibold flex items-center gap-1.5 transition-all hover:opacity-90']"
                            >
                                <FileText class="w-3.5 h-3.5" />
                                <span>{{ t('account.download_invoice') }}</span>
                            </a>
                            <Link
                                :href="'/track-order?order_number=' + order.order_number + '&phone=' + customer.phone"
                                :class="[softPanelClass, 'px-4 py-2 text-xs font-semibold flex items-center gap-1.5 transition-all hover:opacity-90']"
                            >
                                <Truck class="w-3.5 h-3.5" />
                                <span>{{ t('account.track_order') }}</span>
                            </Link>
                            <button
                                v-if="order.can_request_return"
                                type="button"
                                @click="openReturnModal(order)"
                                :class="[primaryButtonClass, 'px-4 py-2 text-xs font-semibold flex items-center gap-1.5 cursor-pointer']"
                            >
                                <RotateCcw class="w-3.5 h-3.5" />
                                <span>{{ t('account.request_return') }}</span>
                            </button>
                            <span
                                v-else-if="order.return_request"
                                class="text-xs font-bold px-2.5 py-1 rounded-full"
                                :class="getStatusBadge(order.return_request.status).class"
                            >
                                {{ t('account.return_open_status', { status: order.return_request.status }) }}
                            </span>
                        </div>
                    </div>

                    <div class="py-4 space-y-3">
                        <div
                            v-for="(item, idx) in order.items"
                            :key="idx"
                            class="flex items-center justify-between text-sm py-1"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="accentTextClass" style="background-color: currentColor"></span>
                                <span class="font-medium" :class="inkClass">{{ item.name }}</span>
                                <span class="text-xs" :class="mutedClass">× {{ item.quantity }}</span>
                            </div>
                            <span class="font-semibold" :class="inkClass">{{ formatPrice(item.line_total) }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-black/5 flex flex-wrap items-center justify-between gap-2 text-xs" :class="mutedClass">
                        <span>{{ t('account.shipping_address', { address: order.shipping_address }) }}</span>
                        <span>{{ t('account.payment_line', { method: paymentLabel(order.payment_method) }) }}</span>
                    </div>

                    <div v-if="order.shipments?.length" class="pt-3 space-y-2">
                        <div
                            v-for="shipment in order.shipments"
                            :key="shipment.shipment_number"
                            class="flex flex-wrap items-center justify-between gap-2 text-sm"
                        >
                            <span :class="inkClass">
                                {{ t('account.shipment_tracking') }}:
                                <span class="font-semibold">{{ shipment.tracking_code || shipment.shipment_number }}</span>
                            </span>
                            <a
                                v-if="shipment.tracking_url"
                                :href="shipment.tracking_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold underline"
                                :class="accentTextClass"
                            >
                                {{ t('account.open_tracking') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div v-if="orders.length === 0" :class="[panelClass, 'p-12 text-center']">
                    <ShoppingBag class="w-16 h-16 opacity-30 mx-auto mb-4" />
                    <h3 class="text-xl font-bold" :class="inkClass">{{ t('account.empty_orders_title') }}</h3>
                    <p class="text-sm mt-1 mb-6" :class="mutedClass">{{ t('account.empty_orders_body') }}</p>
                    <Link
                        href="/"
                        :class="[primaryButtonClass, 'inline-flex items-center gap-2 px-6 py-3 text-sm']"
                    >
                        <span>{{ t('account.shop') }}</span>
                    </Link>
                </div>
            </div>

            <div v-if="activeTab === 'addresses'" class="space-y-6">
                <div :class="[panelClass, 'p-6 sm:p-8 space-y-4']">
                    <h3 class="text-lg font-bold" :class="inkClass">{{ t('account.addresses_title') }}</h3>
                    <div v-if="addresses.length === 0" class="text-sm" :class="mutedClass">{{ t('account.addresses_empty') }}</div>
                    <div v-for="address in addresses" :key="address.id" :class="[softPanelClass, 'p-4 flex flex-col sm:flex-row sm:items-start justify-between gap-3']">
                        <div>
                            <p class="font-semibold" :class="inkClass">
                                {{ address.name || customer.name }}
                                <span v-if="address.is_default" class="ml-2 text-[11px] uppercase font-bold opacity-70">{{ t('account.address_default') }}</span>
                            </p>
                            <p class="text-sm mt-1" :class="mutedClass">{{ address.formatted }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-if="!address.is_default"
                                type="button"
                                class="px-3 py-2 text-xs font-semibold"
                                :class="softPanelClass"
                                @click="makeDefaultAddress(address)"
                            >
                                {{ t('account.address_make_default') }}
                            </button>
                            <button
                                type="button"
                                class="px-3 py-2 text-xs font-semibold text-rose-600"
                                @click="deleteAddress(address)"
                            >
                                {{ t('account.address_delete') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="[panelClass, 'p-6 sm:p-8 max-w-2xl']">
                    <h3 class="text-lg font-bold mb-4" :class="inkClass">{{ t('account.address_add') }}</h3>
                    <form class="space-y-4" @submit.prevent="saveAddress">
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('checkout.name') }}</label>
                            <input v-model="addressForm.name" type="text" :class="fieldClass">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('checkout.phone') }}</label>
                            <input v-model="addressForm.phone" type="text" :class="fieldClass">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('account.address_line') }} *</label>
                            <textarea v-model="addressForm.address_line" required rows="3" :class="fieldClass"></textarea>
                            <span v-if="addressForm.errors.address_line" class="text-red-500 text-xs">{{ addressForm.errors.address_line }}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('account.address_area') }}</label>
                                <input v-model="addressForm.area" type="text" :class="fieldClass">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('account.address_city') }} *</label>
                                <input v-model="addressForm.city" type="text" required :class="fieldClass">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('account.address_postcode') }}</label>
                            <input v-model="addressForm.postcode" type="text" :class="fieldClass">
                        </div>
                        <label class="flex items-center gap-2 text-sm" :class="mutedClass">
                            <input v-model="addressForm.is_default" type="checkbox" class="h-4 w-4">
                            <span>{{ t('account.address_make_default') }}</span>
                        </label>
                        <button type="submit" :disabled="addressForm.processing" :class="[primaryButtonClass, 'px-5 py-3 text-sm']">
                            {{ addressForm.processing ? t('account.address_saving') : t('account.address_save') }}
                        </button>
                    </form>
                </div>
            </div>

            <div v-if="activeTab === 'returns'" class="space-y-6">
                <div
                    v-for="ret in return_requests"
                    :key="ret.id"
                    :class="[panelClass, 'p-6 sm:p-8']"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <h3 class="text-lg font-bold" :class="inkClass">{{ t('account.order') }} #{{ ret.order_number }}</h3>
                                <span v-if="ret.rma_number" class="text-xs font-semibold" :class="mutedClass">{{ ret.rma_number }}</span>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full" :class="getStatusBadge(ret.status).class">
                                    {{ ret.status_label || ret.status }}
                                </span>
                            </div>
                            <p class="text-xs mt-1" :class="mutedClass">{{ ret.created_at }}</p>
                        </div>
                        <div class="text-sm font-semibold" :class="inkClass">
                            {{ t('account.return_refund_amount', { amount: formatPrice(ret.refund_amount) }) }}
                        </div>
                    </div>
                    <p class="text-sm mt-4" :class="mutedClass">{{ ret.reason }}</p>
                    <p
                        v-if="ret.admin_note"
                        class="text-sm mt-2 text-rose-700"
                    >
                        {{ t('account.return_admin_note', { note: ret.admin_note }) }}
                    </p>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs" :class="mutedClass">{{ t('account.return_items_count', { count: ret.items_count || 0 }) }}</p>
                        <button
                            v-if="ret.can_cancel"
                            type="button"
                            class="text-xs font-semibold text-rose-700 hover:text-rose-800 underline-offset-2 hover:underline"
                            @click="cancelReturn(ret)"
                        >
                            {{ t('account.return_cancel_request') }}
                        </button>
                    </div>
                </div>

                <div v-if="return_requests.length === 0" :class="[panelClass, 'p-12 text-center']">
                    <RotateCcw class="w-16 h-16 opacity-30 mx-auto mb-4" />
                    <h3 class="text-xl font-bold" :class="inkClass">{{ t('account.return_empty_title') }}</h3>
                    <p class="text-sm mt-1" :class="mutedClass">{{ t('account.return_empty_body') }}</p>
                </div>
            </div>

            <div v-if="activeTab === 'notifications'" class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold" :class="inkClass">{{ t('account.notifications_tab', { count: unreadNotifications }) }}</h2>
                    <button
                        v-if="unreadNotifications > 0"
                        type="button"
                        class="text-xs font-semibold underline-offset-2 hover:underline cursor-pointer"
                        :class="accentTextClass"
                        @click="markAllNotificationsRead"
                    >
                        {{ t('account.notifications_mark_all_read') }}
                    </button>
                </div>

                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    :class="[panelClass, 'p-5', notification.read ? 'opacity-70' : '']"
                >
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold" :class="inkClass">{{ notification.title }}</h3>
                            <p class="text-sm mt-1" :class="mutedClass">{{ notification.body }}</p>
                            <p class="text-xs mt-2" :class="mutedClass">{{ notification.created_at }}</p>
                        </div>
                        <button
                            v-if="!notification.read"
                            type="button"
                            class="text-xs font-semibold text-emerald-700 hover:underline cursor-pointer shrink-0"
                            @click="markNotificationRead(notification)"
                        >
                            {{ t('account.notification_mark_read') }}
                        </button>
                    </div>
                </div>

                <div v-if="notifications.length === 0" :class="[panelClass, 'p-12 text-center']">
                    <Bell class="w-16 h-16 opacity-30 mx-auto mb-4" />
                    <h3 class="text-xl font-bold" :class="inkClass">{{ t('account.notifications_empty_title') }}</h3>
                    <p class="text-sm mt-1" :class="mutedClass">{{ t('account.notifications_empty_body') }}</p>
                </div>
            </div>

            <div v-if="activeTab === 'profile'" :class="[panelClass, 'max-w-2xl p-8']">
                <h2 class="text-xl font-bold mb-6 pb-3 border-b border-black/5" :class="inkClass">{{ t('account.update_profile_title') }}</h2>

                <form @submit.prevent="updateProfile" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('auth.name') }} *</label>
                        <input
                            v-model="profileForm.name"
                            type="text"
                            required
                            :class="fieldClass"
                        />
                        <p v-if="profileForm.errors.name" class="text-xs text-red-500 mt-1 font-medium">{{ profileForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('account.phone_locked') }}</label>
                        <input
                            :value="customer.phone"
                            disabled
                            type="text"
                            class="w-full px-4 py-2.5 text-sm border border-gray-200 bg-gray-100 text-gray-500 cursor-not-allowed"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('auth.email') }}</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            placeholder="user@example.com"
                            :class="fieldClass"
                        />
                        <p v-if="profileForm.errors.email" class="text-xs text-red-500 mt-1 font-medium">{{ profileForm.errors.email }}</p>
                    </div>

                    <div class="pt-4 border-t border-black/5">
                        <h3 class="text-sm font-bold mb-4" :class="inkClass">{{ t('account.change_password') }}</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('account.current_password') }}</label>
                                <input
                                    v-model="profileForm.current_password"
                                    type="password"
                                    placeholder="••••••••"
                                    :class="fieldClass"
                                />
                                <p v-if="profileForm.errors.current_password" class="text-xs text-red-500 mt-1 font-medium">{{ profileForm.errors.current_password }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('account.new_password') }}</label>
                                <input
                                    v-model="profileForm.new_password"
                                    type="password"
                                    placeholder="••••••••"
                                    :class="fieldClass"
                                />
                                <p v-if="profileForm.errors.new_password" class="text-xs text-red-500 mt-1 font-medium">{{ profileForm.errors.new_password }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('account.confirm_new_password') }}</label>
                                <input
                                    v-model="profileForm.new_password_confirmation"
                                    type="password"
                                    placeholder="••••••••"
                                    :class="fieldClass"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            :class="[primaryButtonClass, 'px-6 py-2.5 text-sm cursor-pointer']"
                        >
                            {{ profileForm.processing ? t('account.saving') : t('account.save') }}
                        </button>
                    </div>
                </form>
            </div>

            <div
                v-if="returnOrder"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/45"
                @click.self="closeReturnModal"
            >
                <div :class="[panelClass, 'w-full max-w-lg p-6 sm:p-8 max-h-[90vh] overflow-y-auto']">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-xl font-bold" :class="inkClass">{{ t('account.return_modal_title') }}</h3>
                            <p class="text-sm mt-1" :class="mutedClass">
                                {{ t('account.return_modal_help', { days: return_window_days }) }}
                            </p>
                        </div>
                        <button type="button" class="p-1 cursor-pointer" :class="mutedClass" @click="closeReturnModal">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <p class="text-sm font-semibold mb-4" :class="inkClass">
                        {{ t('account.order') }} #{{ returnOrder.order_number }}
                    </p>

                    <form @submit.prevent="submitReturn" class="space-y-5">
                        <div>
                            <label class="block text-xs font-semibold uppercase mb-2" :class="mutedClass">
                                {{ t('account.return_select_items') }}
                            </label>
                            <div class="space-y-2">
                                <label
                                    v-for="item in returnForm.items"
                                    :key="item.order_item_id"
                                    :class="[softPanelClass, 'flex items-center gap-3 p-3 cursor-pointer']"
                                >
                                    <input v-model="item.selected" type="checkbox" class="rounded border-gray-300" />
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-medium block" :class="inkClass">{{ item.name }}</span>
                                        <span class="text-xs" :class="mutedClass">{{ t('account.return_remaining_qty', { count: item.max }) }}</span>
                                    </div>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="1"
                                        :max="item.max"
                                        :disabled="!item.selected"
                                        class="w-16 px-2 py-1 text-sm border border-black/10 rounded"
                                    />
                                </label>
                            </div>
                            <p v-if="returnForm.errors.items" class="text-xs text-red-500 mt-1 font-medium">{{ returnForm.errors.items }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">
                                {{ t('account.return_reason') }} *
                            </label>
                            <textarea
                                v-model="returnForm.reason"
                                rows="3"
                                required
                                :placeholder="t('account.return_reason_placeholder')"
                                :class="fieldClass"
                            />
                            <p v-if="returnForm.errors.reason" class="text-xs text-red-500 mt-1 font-medium">{{ returnForm.errors.reason }}</p>
                            <p v-if="returnForm.errors.order" class="text-xs text-red-500 mt-1 font-medium">{{ returnForm.errors.order }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-semibold cursor-pointer"
                                :class="mutedClass"
                                @click="closeReturnModal"
                            >
                                {{ t('account.return_cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="returnForm.processing || selectedReturnItems.length === 0"
                                :class="[primaryButtonClass, 'px-5 py-2.5 text-sm cursor-pointer']"
                            >
                                {{ returnForm.processing ? t('account.return_submitting') : t('account.return_submit') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
