<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ChevronRight, ArrowRight, ShoppingBag, Truck } from 'lucide-vue-next';
import Header from '../Components/Header.vue';
import Footer from '../Components/Footer.vue';
import { useCart } from '../Composables/useCart';

const { cartItems, totalAmount, totalCount, clearCart } = useCart();

const shippingCharge = ref(60);

const form = useForm({
    customer_name: '',
    customer_phone: '',
    shipping_city: 'Dhaka',
    shipping_address_line: '',
    shipping_amount: shippingCharge.value,
    cart_items: cartItems.value.map(item => ({
        id: item.id,
        variant_id: item.variant_id,
        quantity: item.quantity
    }))
});

const formatPrice = (val) => {
    return '৳ ' + Number(val).toLocaleString('en-US');
};

const grandTotal = computed(() => {
    return totalAmount.value + shippingCharge.value;
});

const submitOrder = () => {
    form.post('/checkout', {
        preserveScroll: true,
        onSuccess: () => {
            clearCart();
        }
    });
};

const updateShipping = () => {
    shippingCharge.value = form.shipping_city === 'Dhaka' ? 60 : 120;
    form.shipping_amount = shippingCharge.value;
};
</script>

<template>
    <Head title="Checkout - Nobo Shop" />

    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col">
        <Header />

        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            <nav class="flex items-center gap-2 text-sm text-[#64748B] mb-8">
                <Link href="/" class="hover:text-[#E86A28] transition-colors">হোম</Link>
                <ChevronRight class="w-4 h-4" />
                <span class="text-[#1E293B] font-medium">চেকআউট</span>
            </nav>

            <div v-if="cartItems.length === 0" class="text-center py-20">
                <ShoppingBag class="w-16 h-16 mx-auto text-[#DCCFC0] mb-4" />
                <h2 class="text-2xl font-bold text-[#1E293B] mb-2">আপনার কার্ট খালি!</h2>
                <p class="text-[#64748B] mb-6">দয়া করে কিছু প্রোডাক্ট কার্টে যোগ করুন।</p>
                <Link href="/" class="inline-flex items-center gap-2 px-6 py-3 bg-[#E86A28] text-white rounded-xl font-bold">
                    শপে ফিরে যান
                </Link>
            </div>

            <div v-else class="flex flex-col lg:flex-row gap-8">
                <!-- Checkout Form -->
                <div class="w-full lg:w-2/3 bg-white p-6 md:p-8 rounded-3xl border border-[#EAE3D6] shadow-sm">
                    <h2 class="text-2xl font-bold text-[#1E293B] mb-6 border-b border-[#F0EAE1] pb-4">ডেলিভারি ইনফরমেশন</h2>
                    
                    <form @submit.prevent="submitOrder" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-[#475569] mb-2">আপনার নাম *</label>
                                <input v-model="form.customer_name" type="text" required class="w-full border border-[#E2D9CD] rounded-xl px-4 py-3 bg-[#FAF7F2] focus:ring-2 focus:ring-[#E86A28]/20 focus:border-[#E86A28] transition-all" placeholder="যেমন: রহিম মিয়া">
                                <span v-if="form.errors.customer_name" class="text-red-500 text-xs mt-1">{{ form.errors.customer_name }}</span>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-[#475569] mb-2">মোবাইল নাম্বার *</label>
                                <input v-model="form.customer_phone" type="text" required class="w-full border border-[#E2D9CD] rounded-xl px-4 py-3 bg-[#FAF7F2] focus:ring-2 focus:ring-[#E86A28]/20 focus:border-[#E86A28] transition-all" placeholder="01XXX-XXXXXX">
                                <span v-if="form.errors.customer_phone" class="text-red-500 text-xs mt-1">{{ form.errors.customer_phone }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-[#475569] mb-2">শহর *</label>
                            <select v-model="form.shipping_city" @change="updateShipping" required class="w-full border border-[#E2D9CD] rounded-xl px-4 py-3 bg-[#FAF7F2] focus:ring-2 focus:ring-[#E86A28]/20 focus:border-[#E86A28] transition-all">
                                <option value="Dhaka">ঢাকা</option>
                                <option value="Outside Dhaka">ঢাকার বাইরে</option>
                            </select>
                            <span v-if="form.errors.shipping_city" class="text-red-500 text-xs mt-1">{{ form.errors.shipping_city }}</span>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-[#475569] mb-2">সম্পূর্ণ ঠিকানা *</label>
                            <textarea v-model="form.shipping_address_line" required rows="3" class="w-full border border-[#E2D9CD] rounded-xl px-4 py-3 bg-[#FAF7F2] focus:ring-2 focus:ring-[#E86A28]/20 focus:border-[#E86A28] transition-all" placeholder="বাসা নং, রাস্তা নং, এরিয়া"></textarea>
                            <span v-if="form.errors.shipping_address_line" class="text-red-500 text-xs mt-1">{{ form.errors.shipping_address_line }}</span>
                        </div>

                        <div class="pt-6 border-t border-[#F0EAE1]">
                            <button type="submit" :disabled="form.processing" class="w-full py-4 bg-[#E86A28] hover:bg-[#D35B1D] text-white font-bold text-lg rounded-xl shadow-md transition-all flex items-center justify-center gap-2 disabled:opacity-70">
                                <span>অর্ডার কনফার্ম করুন</span>
                                <ArrowRight class="w-5 h-5" />
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-white p-6 rounded-3xl border border-[#EAE3D6] shadow-sm sticky top-8">
                        <h3 class="text-lg font-bold text-[#1E293B] mb-4">অর্ডার সামারি</h3>
                        
                        <div class="space-y-4 mb-6 max-h-60 overflow-y-auto pr-2">
                            <div v-for="item in cartItems" :key="item.cartItemId || item.id" class="flex gap-4">
                                <img :src="item.image" :alt="item.name" class="w-16 h-16 rounded-lg object-cover bg-[#F7F3EC] border border-[#EAE3D6]">
                                <div class="flex-1">
                                    <h4 class="text-sm font-bold text-[#1E293B] line-clamp-2">{{ item.name }}</h4>
                                    <div class="text-sm text-[#64748B] mt-1">{{ formatPrice(item.price) }} x {{ item.quantity }}</div>
                                </div>
                                <div class="text-sm font-bold text-[#E86A28]">{{ formatPrice(item.price * item.quantity) }}</div>
                            </div>
                        </div>

                        <div class="border-t border-[#F0EAE1] pt-4 space-y-3">
                            <div class="flex justify-between text-sm font-medium text-[#64748B]">
                                <span>সাবটোটাল ({{ totalCount }} আইটেম)</span>
                                <span>{{ formatPrice(totalAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-medium text-[#64748B]">
                                <span>ডেলিভারি চার্জ</span>
                                <span>{{ formatPrice(shippingCharge) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold text-[#1E293B] pt-3 border-t border-[#F0EAE1]">
                                <span>সর্বমোট</span>
                                <span class="text-[#E86A28]">{{ formatPrice(grandTotal) }}</span>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-3 text-sm text-[#475569] bg-[#FAF7F2] p-4 rounded-xl">
                            <Truck class="w-5 h-5 text-[#E86A28]" />
                            <span>ক্যাশ অন ডেলিভারি (পণ্য হাতে পেয়ে টাকা দিন)</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
