<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Package, MapPin, Truck, CheckCircle2, ChevronRight, AlertCircle, ShoppingBag } from 'lucide-vue-next';
import Header from '../Components/Header.vue';
import Footer from '../Components/Footer.vue';

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
    const labelMap = {
        'pending': 'পেন্ডিং',
        'confirmed': 'কনফার্মড',
        'processing': 'প্রসেসিং',
        'shipped': 'শিপ করা হয়েছে',
        'delivered': 'ডেলিভারি সম্পন্ন',
        'cancelled': 'বাতিল করা হয়েছে',
        'returned': 'ফেরত দেওয়া হয়েছে',
    };
    return labelMap[status?.toLowerCase()] || status;
};

// Define steps for progress bar
const steps = [
    { key: 'pending', label: 'অর্ডার প্লেসড', icon: ShoppingBag },
    { key: 'confirmed', label: 'কনফার্মড', icon: CheckCircle2 },
    { key: 'processing', label: 'প্যাকেজিং', icon: Package },
    { key: 'shipped', label: 'শিপিং', icon: Truck },
    { key: 'delivered', label: 'ডেলিভারড', icon: MapPin },
];

const getCurrentStepIndex = () => {
    if (!props.order) return -1;
    const currentStatus = props.order.status.toLowerCase();
    
    if (currentStatus === 'cancelled' || currentStatus === 'returned') return -1;
    
    const index = steps.findIndex(s => s.key === currentStatus);
    return index !== -1 ? index : 0;
};
</script>

<template>
    <Head title="Track Order - Nobo Shop" />

    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col">
        <Header />

        <main class="flex-1 max-w-4xl mx-auto px-4 sm:px-6 py-8 w-full">
            <nav class="flex items-center gap-2 text-sm text-[#64748B] mb-8">
                <Link href="/" class="hover:text-[#E86A28] transition-colors">হোম</Link>
                <ChevronRight class="w-4 h-4" />
                <span class="text-[#1E293B] font-medium">ট্র্যাক অর্ডার</span>
            </nav>

            <div class="bg-white rounded-3xl p-6 md:p-8 border border-[#EAE3D6] shadow-sm mb-8">
                <div class="text-center mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-[#1E293B] mb-2">আপনার অর্ডার ট্র্যাক করুন</h1>
                    <p class="text-[#64748B]">অর্ডারের বর্তমান অবস্থা জানতে নিচের বক্সে আপনার অর্ডার নম্বর দিন।</p>
                </div>

                <form @submit.prevent="handleSearch" class="max-w-2xl mx-auto flex flex-col md:flex-row gap-4">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-[#94A3B8]">
                            <Search class="w-5 h-5" />
                        </div>
                        <input
                            v-model="orderNumber"
                            type="text"
                            required
                            placeholder="অর্ডার নম্বর (যেমন: ORD-XXXXXX)"
                            class="w-full pl-11 pr-4 py-3.5 bg-[#FAF7F2] border border-[#E2D9CD] rounded-xl text-[15px] focus:bg-white focus:border-[#E86A28] focus:ring-2 focus:ring-[#E86A28]/20 transition-all outline-none"
                        />
                    </div>
                    <div class="md:w-1/3">
                        <input
                            v-model="phone"
                            type="text"
                            placeholder="মোবাইল নম্বর (ঐচ্ছিক)"
                            class="w-full px-4 py-3.5 bg-[#FAF7F2] border border-[#E2D9CD] rounded-xl text-[15px] focus:bg-white focus:border-[#E86A28] focus:ring-2 focus:ring-[#E86A28]/20 transition-all outline-none"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="isSearching"
                        class="px-8 py-3.5 bg-[#E86A28] hover:bg-[#D35B1D] text-white font-bold rounded-xl shadow-sm hover:shadow-md transition-all whitespace-nowrap disabled:opacity-70 flex items-center justify-center"
                    >
                        {{ isSearching ? 'খুঁজছে...' : 'ট্র্যাক করুন' }}
                    </button>
                </form>

                <div v-if="error" class="mt-6 max-w-2xl mx-auto p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3 text-red-600">
                    <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
                    <span class="text-sm font-medium">{{ error }}</span>
                </div>
            </div>

            <!-- Order Details -->
            <div v-if="order" class="space-y-6">
                <!-- Status Header -->
                <div class="bg-white rounded-3xl p-6 md:p-8 border border-[#EAE3D6] shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h2 class="text-xl font-bold text-[#1E293B] mb-2">অর্ডার #{{ order.order_number }}</h2>
                        <div class="flex items-center gap-3 text-sm text-[#64748B]">
                            <span>{{ order.placed_at }}</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-[#DCCFC0]"></span>
                            <span>{{ order.items.length }} আইটেম</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="px-4 py-1.5 rounded-full text-sm font-bold border" :class="getStatusColor(order.status)">
                            {{ getStatusLabel(order.status) }}
                        </span>
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-medium text-[#64748B]">সর্বমোট</div>
                            <div class="text-xl font-bold text-[#E86A28]">{{ formatPrice(order.grand_total) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div v-if="getCurrentStepIndex() !== -1" class="bg-white rounded-3xl p-6 md:p-8 border border-[#EAE3D6] shadow-sm overflow-x-auto">
                    <div class="min-w-[600px]">
                        <div class="flex justify-between relative">
                            <!-- Track line -->
                            <div class="absolute left-8 right-8 top-6 h-1 bg-[#F0EAE1] -z-10 rounded-full"></div>
                            <div 
                                class="absolute left-8 top-6 h-1 bg-[#E86A28] -z-10 rounded-full transition-all duration-500"
                                :style="{ width: `calc(${(getCurrentStepIndex() / (steps.length - 1)) * 100}% - 4rem)` }"
                            ></div>

                            <div v-for="(step, index) in steps" :key="step.key" class="flex flex-col items-center w-24">
                                <div 
                                    class="w-12 h-12 rounded-full flex items-center justify-center mb-3 transition-colors duration-300 border-2 bg-white"
                                    :class="index <= getCurrentStepIndex() ? 'border-[#E86A28] text-[#E86A28]' : 'border-[#EAE3D6] text-[#94A3B8]'"
                                >
                                    <component :is="step.icon" class="w-5 h-5" />
                                </div>
                                <span 
                                    class="text-xs font-bold text-center"
                                    :class="index <= getCurrentStepIndex() ? 'text-[#1E293B]' : 'text-[#94A3B8]'"
                                >
                                    {{ step.label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cancelled/Returned Message -->
                <div v-else class="bg-white rounded-3xl p-6 border border-[#EAE3D6] shadow-sm flex items-center gap-4">
                    <AlertCircle class="w-8 h-8 text-rose-500 shrink-0" />
                    <div>
                        <h3 class="text-lg font-bold text-[#1E293B]">এই অর্ডারটি {{ getStatusLabel(order.status) }}</h3>
                        <p class="text-[#64748B] text-sm mt-1">যেকোনো তথ্যের জন্য আমাদের কাস্টমার সার্ভিসের সাথে যোগাযোগ করুন।</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Items -->
                    <div class="md:col-span-2 bg-white rounded-3xl p-6 border border-[#EAE3D6] shadow-sm">
                        <h3 class="font-bold text-[#1E293B] mb-4 border-b border-[#F0EAE1] pb-3">অর্ডারকৃত আইটেম</h3>
                        <div class="divide-y divide-[#F0EAE1]">
                            <div v-for="(item, index) in order.items" :key="index" class="py-3 flex justify-between items-center">
                                <div>
                                    <div class="font-semibold text-[#1E293B] text-sm">{{ item.name }}</div>
                                    <div class="text-xs text-[#64748B] mt-1">{{ formatPrice(item.price) }} x {{ item.quantity }}</div>
                                </div>
                                <div class="font-bold text-[#1E293B]">{{ formatPrice(item.total) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="bg-white rounded-3xl p-6 border border-[#EAE3D6] shadow-sm space-y-6">
                        <div>
                            <h3 class="font-bold text-[#1E293B] mb-3 text-sm uppercase tracking-wider text-[#64748B]">ডেলিভারি ঠিকানা</h3>
                            <div class="text-sm text-[#1E293B] font-medium">{{ order.customer_name }}</div>
                            <div class="text-sm text-[#64748B] mt-1">{{ order.customer_phone }}</div>
                            <div class="text-sm text-[#64748B] mt-1">{{ order.shipping_address }}</div>
                        </div>

                        <div class="border-t border-[#F0EAE1] pt-6">
                            <h3 class="font-bold text-[#1E293B] mb-3 text-sm uppercase tracking-wider text-[#64748B]">পেমেন্ট তথ্য</h3>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-[#64748B]">পেমেন্ট মেথড</span>
                                <span class="font-medium text-[#1E293B] capitalize">{{ order.payment_method }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-[#64748B]">পেমেন্ট স্ট্যাটাস</span>
                                <span class="font-medium px-2 py-0.5 rounded text-xs"
                                    :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                >
                                    {{ order.payment_status === 'paid' ? 'পেইড' : 'আনপেইড' }}
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
