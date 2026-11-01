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

    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col justify-between">
        <Header />

        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            <nav class="flex items-center gap-2 text-sm text-[#64748B] mb-6">
                <Link href="/" class="hover:text-[#E86A28] transition-colors">{{ t('header.home') }}</Link>
                <ChevronRight class="w-4 h-4" />
                <span class="text-[#1E293B] font-medium">{{ t('account.title') }}</span>
            </nav>

            <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <span class="text-sm font-semibold">{{ page.props.flash.success }}</span>
            </div>

            <div class="bg-white rounded-3xl border border-[#EAE3D6] p-6 sm:p-8 shadow-sm mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-[#FAF7F2] text-[#E86A28] border border-[#EAE3D6] font-bold text-2xl flex items-center justify-center shrink-0 shadow-2xs">
                            {{ customer.name ? customer.name.charAt(0) : t('reviews.satisfied_customer').charAt(0) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-[#1E293B]">{{ customer.name }}</h1>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-sm text-[#64748B]">
                                <span class="flex items-center gap-1.5">
                                    <Phone class="w-3.5 h-3.5 text-[#94A3B8]" />
                                    {{ customer.phone }}
                                </span>
                                <span v-if="customer.email" class="flex items-center gap-1.5">
                                    <Mail class="w-3.5 h-3.5 text-[#94A3B8]" />
                                    {{ customer.email }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <Calendar class="w-3.5 h-3.5 text-[#94A3B8]" />
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

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-[#F0EAE1]">
                    <div class="bg-[#FAF7F2] p-4 rounded-2xl border border-[#EAE3D6]">
                        <span class="text-xs font-semibold text-[#64748B] block mb-1">{{ t('account.total_orders') }}</span>
                        <span class="text-2xl font-bold text-[#1E293B]">{{ stats.total_orders }}</span>
                    </div>
                    <div class="bg-[#FAF7F2] p-4 rounded-2xl border border-[#EAE3D6]">
                        <span class="text-xs font-semibold text-[#64748B] block mb-1">{{ t('account.total_spent') }}</span>
                        <span class="text-2xl font-bold text-[#E86A28]">{{ formatPrice(stats.total_spent) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 mb-6 border-b border-[#EAE3D6] pb-2">
                <button
                    @click="activeTab = 'orders'"
                    type="button"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'orders' ? 'bg-[#E86A28] text-white shadow-sm' : 'bg-white text-[#64748B] hover:text-[#1E293B] border border-[#EAE3D6]'"
                >
                    <Package class="w-4 h-4" />
                    <span>{{ t('account.orders_tab', { count: orders.length }) }}</span>
                </button>

                <button
                    @click="activeTab = 'profile'"
                    type="button"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'profile' ? 'bg-[#E86A28] text-white shadow-sm' : 'bg-white text-[#64748B] hover:text-[#1E293B] border border-[#EAE3D6]'"
                >
                    <Edit3 class="w-4 h-4" />
                    <span>{{ t('account.profile_settings') }}</span>
                </button>
            </div>

            <div v-if="activeTab === 'orders'" class="space-y-6">
                <div
                    v-for="order in orders"
                    :key="order.id"
                    class="bg-white rounded-3xl border border-[#EAE3D6] p-6 sm:p-8 shadow-sm transition-all hover:border-[#DCCFC0]"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#F0EAE1]">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-bold text-[#1E293B]">{{ t('account.order') }} #{{ order.order_number }}</h3>
                                <span 
                                    class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                    :class="getStatusBadge(order.status).class"
                                >
                                    {{ getStatusBadge(order.status).text }}
                                </span>
                            </div>
                            <span class="text-xs text-[#64748B] mt-1 block">
                                {{ t('account.order_date', { date: order.created_at }) }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-lg font-bold text-[#1E293B]">
                                {{ formatPrice(order.grand_total) }}
                            </span>
                            <Link
                                :href="'/track-order?order_number=' + order.order_number + '&phone=' + customer.phone"
                                class="px-4 py-2 bg-[#FAF7F2] hover:bg-[#E86A28] text-[#1E293B] hover:text-white border border-[#EAE3D6] hover:border-[#E86A28] rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all shadow-2xs"
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
                                <span class="w-2 h-2 rounded-full bg-[#E86A28]"></span>
                                <span class="font-medium text-[#1E293B]">{{ item.name }}</span>
                                <span class="text-xs text-[#64748B]">× {{ item.quantity }}</span>
                            </div>
                            <span class="font-semibold text-[#1E293B]">{{ formatPrice(item.line_total) }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#F0EAE1] flex flex-wrap items-center justify-between gap-2 text-xs text-[#64748B]">
                        <span>{{ t('account.shipping_address', { address: order.shipping_address }) }}</span>
                        <span>{{ t('account.payment_line', { method: paymentLabel(order.payment_method) }) }}</span>
                    </div>
                </div>

                <div v-if="orders.length === 0" class="bg-white rounded-3xl border border-[#EAE3D6] p-12 text-center shadow-sm">
                    <ShoppingBag class="w-16 h-16 text-[#CBD5E1] mx-auto mb-4" />
                    <h3 class="text-xl font-bold text-[#1E293B]">{{ t('account.empty_orders_title') }}</h3>
                    <p class="text-sm text-[#64748B] mt-1 mb-6">{{ t('account.empty_orders_body') }}</p>
                    <Link
                        href="/"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-[#E86A28] hover:bg-[#D35B1D] text-white rounded-xl font-bold text-sm shadow-md transition-all"
                    >
                        <span>{{ t('account.shop') }}</span>
                    </Link>
                </div>
            </div>

            <div v-if="activeTab === 'profile'" class="max-w-2xl bg-white rounded-3xl border border-[#EAE3D6] p-8 shadow-sm">
                <h2 class="text-xl font-bold text-[#1E293B] mb-6 pb-3 border-b border-[#F0EAE1]">{{ t('account.update_profile_title') }}</h2>

                <form @submit.prevent="updateProfile" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('auth.name') }} *</label>
                        <input
                            v-model="profileForm.name"
                            type="text"
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                        />
                        <p v-if="profileForm.errors.name" class="text-xs text-red-500 mt-1 font-medium">{{ profileForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('account.phone_locked') }}</label>
                        <input
                            :value="customer.phone"
                            disabled
                            type="text"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 bg-gray-100 text-gray-500 cursor-not-allowed"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('auth.email') }}</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            placeholder="user@example.com"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                        />
                        <p v-if="profileForm.errors.email" class="text-xs text-red-500 mt-1 font-medium">{{ profileForm.errors.email }}</p>
                    </div>

                    <div class="pt-4 border-t border-[#F0EAE1]">
                        <h3 class="text-sm font-bold text-[#1E293B] mb-4">{{ t('account.change_password') }}</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('account.current_password') }}</label>
                                <input
                                    v-model="profileForm.current_password"
                                    type="password"
                                    placeholder="••••••••"
                                    class="w-full px-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                                <p v-if="profileForm.errors.current_password" class="text-xs text-red-500 mt-1 font-medium">{{ profileForm.errors.current_password }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('account.new_password') }}</label>
                                <input
                                    v-model="profileForm.new_password"
                                    type="password"
                                    placeholder="••••••••"
                                    class="w-full px-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                                <p v-if="profileForm.errors.new_password" class="text-xs text-red-500 mt-1 font-medium">{{ profileForm.errors.new_password }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('account.confirm_new_password') }}</label>
                                <input
                                    v-model="profileForm.new_password_confirmation"
                                    type="password"
                                    placeholder="••••••••"
                                    class="w-full px-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="px-6 py-2.5 bg-[#E86A28] hover:bg-[#D35B1D] text-white rounded-xl font-bold text-sm transition-all shadow-sm disabled:opacity-50 cursor-pointer"
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
