<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { 
    Package, ShoppingBag, LogOut, Phone, Mail, Calendar, 
    Truck, ChevronRight, CheckCircle2, Edit3
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
const activeTab = ref('orders');

const profileForm = useForm({
    name: props.customer.name,
    email: props.customer.email || '',
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

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
        cancelled: { text: t('account.status_cancelled'), class: 'bg-rose-100 text-rose-800' },
    };
    return badges[status] || { text: status, class: 'bg-gray-100 text-gray-800' };
};

const paymentLabel = (method) => {
    return method === 'cod' ? t('account.payment_cod') : method;
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

            <div class="flex items-center gap-2 mb-6 border-b border-black/10 pb-2">
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

                        <div class="flex items-center gap-3">
                            <span class="text-lg font-bold" :class="inkClass">
                                {{ formatPrice(order.grand_total) }}
                            </span>
                            <Link
                                :href="'/track-order?order_number=' + order.order_number + '&phone=' + customer.phone"
                                :class="[softPanelClass, 'px-4 py-2 text-xs font-semibold flex items-center gap-1.5 transition-all hover:opacity-90']"
                            >
                                <Truck class="w-3.5 h-3.5" />
                                <span>{{ t('account.track_order') }}</span>
                            </Link>
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
        </main>

        <Footer />
    </div>
</template>
