<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ChevronRight, ArrowRight, ShoppingBag, Truck, Tag, X, AlertCircle, Wallet } from 'lucide-vue-next';
import Header from '../Components/Header.vue';
import Footer from '../Components/Footer.vue';
import ProductCard from '../Components/ProductCard.vue';
import { useCart, setCartContact } from '../Composables/useCart';
import { useI18n } from '../Composables/useI18n';
import { useStorefrontPageShell } from '../Composables/useStorefrontPageShell';

const props = defineProps({
    payment_methods: {
        type: Array,
        default: () => [],
    },
    shipping_methods: {
        type: Array,
        default: () => [],
    },
    suggestedProducts: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const { t } = useI18n();
const {
    pageClass, panelClass, primaryButtonClass, accentTextClass, fieldClass, inkClass, mutedClass,
    softPanelClass, choiceSelectedClass, choiceIdleClass,
} = useStorefrontPageShell();
const { cartItems, totalAmount, totalCount } = useCart();

const methods = computed(() => (props.payment_methods?.length ? props.payment_methods : []));

const shippingDhaka = computed(() => Number(page.props.site_settings?.shipping_inside_dhaka || 60));
const shippingOutside = computed(() => Number(page.props.site_settings?.shipping_outside_dhaka || 120));

const methodsForCity = (city) => {
    const zone = city === 'Dhaka' ? 'inside' : 'outside';
    const zoned = props.shipping_methods.filter((method) => method.zone === zone || method.zone === 'any');

    return zoned.length ? zoned : props.shipping_methods;
};

const methodCharge = (method) => {
    if (method?.free_shipping_threshold && totalAmount.value >= Number(method.free_shipping_threshold)) {
        return 0;
    }

    return Number(method?.charge || 0);
};

const loggedInCustomer = page.props.customer;
const initialCityMethods = methodsForCity('Dhaka');

const form = useForm({
    customer_name: loggedInCustomer ? loggedInCustomer.name : '',
    customer_phone: loggedInCustomer ? loggedInCustomer.phone : '',
    customer_email: loggedInCustomer?.email || '',
    shipping_city: 'Dhaka',
    shipping_method_id: initialCityMethods[0]?.id || null,
    shipping_address_line: '',
    shipping_amount: initialCityMethods[0] ? methodCharge(initialCityMethods[0]) : shippingDhaka.value,
    coupon_code: null,
    payment_method: methods.value[0]?.key || 'cod',
    transaction_reference: '',
    cart_items: cartItems.value.map(item => ({
        id: item.id,
        variant_id: item.variant_id || item.id,
        quantity: item.quantity
    }))
});

const selectedShipping = computed(() => methodsForCity(form.shipping_city).find((method) => method.id === form.shipping_method_id) || null);

const shippingCharge = computed(() => {
    if (selectedShipping.value) {
        return methodCharge(selectedShipping.value);
    }

    return form.shipping_city === 'Dhaka' ? shippingDhaka.value : shippingOutside.value;
});

const couponCode = ref('');
const appliedCoupon = ref(null);
const discountAmount = ref(0);
const couponError = ref('');
const applyingCoupon = ref(false);

const applyCoupon = async () => {
    if (!couponCode.value.trim()) return;
    
    applyingCoupon.value = true;
    couponError.value = '';
    
    try {
        const response = await axios.post('/checkout/apply-coupon', {
            code: couponCode.value,
            subtotal: totalAmount.value
        });
        
        appliedCoupon.value = response.data.code;
        discountAmount.value = response.data.discount;
        form.coupon_code = response.data.code;
        couponCode.value = ''; 
    } catch (error) {
        couponError.value = error.response?.data?.message || t('checkout.coupon_error');
        appliedCoupon.value = null;
        discountAmount.value = 0;
        form.coupon_code = null;
    } finally {
        applyingCoupon.value = false;
    }
};

const removeCoupon = () => {
    appliedCoupon.value = null;
    discountAmount.value = 0;
    form.coupon_code = null;
    couponError.value = '';
};

const formatPrice = (val) => {
    return '৳ ' + Number(val).toLocaleString('en-US');
};

const grandTotal = computed(() => {
    return totalAmount.value - discountAmount.value + shippingCharge.value;
});

const selectedMethod = computed(() => methods.value.find((method) => method.key === form.payment_method) || methods.value[0] || null);

const paymentError = computed(() => form.errors.payment || form.errors.payment_method || page.props.errors?.payment || '');

const submitOrder = () => {
    form.shipping_amount = shippingCharge.value;
    form.cart_items = cartItems.value.map(item => ({
        id: item.id,
        variant_id: item.variant_id || item.id,
        quantity: item.quantity
    }));
    form.post('/checkout', {
        preserveScroll: true,
    });
};

const updateShipping = () => {
    const available = methodsForCity(form.shipping_city);
    if (!available.some((method) => method.id === form.shipping_method_id)) {
        form.shipping_method_id = available[0]?.id || null;
    }
    form.shipping_amount = shippingCharge.value;
};

watch(
    () => [form.customer_name, form.customer_phone, form.customer_email],
    () => {
        setCartContact({
            name: form.customer_name,
            phone: form.customer_phone,
            email: form.customer_email,
        });
    },
);
</script>

<template>
    <Head :title="t('meta.checkout')" />

    <div :class="pageClass">
        <Header />

        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            <nav class="flex items-center gap-2 text-sm mb-8" :class="mutedClass">
                <Link href="/" class="hover:opacity-80 transition-opacity" :class="accentTextClass">{{ t('header.home') }}</Link>
                <ChevronRight class="w-4 h-4" />
                <span class="font-medium" :class="inkClass">{{ t('checkout.title') }}</span>
            </nav>

            <div v-if="cartItems.length === 0" class="py-12">
                <div class="text-center mb-10">
                    <ShoppingBag class="w-16 h-16 mx-auto mb-4 opacity-40" />
                    <h2 class="text-2xl font-bold mb-2" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('checkout.empty') }}</h2>
                    <p class="mb-6" :class="mutedClass">{{ t('checkout.empty_hint') }}</p>
                    <Link href="/" :class="[primaryButtonClass, 'px-6 py-3']">
                        {{ t('checkout.back_home') }}
                    </Link>
                </div>
                <section v-if="suggestedProducts.length" class="max-w-6xl mx-auto">
                    <h3 class="text-lg font-semibold mb-4" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('checkout.suggested') }}</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-3 lg:gap-4">
                        <ProductCard v-for="item in suggestedProducts" :key="item.id" :product="item" />
                    </div>
                </section>
            </div>

            <div v-else class="flex flex-col lg:flex-row gap-8">
                <!-- Checkout Form -->
                <div :class="[panelClass, 'w-full lg:w-2/3 p-6 md:p-8']">
                    <h2 class="text-2xl font-bold mb-6 border-b border-black/5 pb-4" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('checkout.delivery_info') }}</h2>
                    
                    <form @submit.prevent="submitOrder" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('checkout.name') }} *</label>
                                <input v-model="form.customer_name" type="text" required :class="fieldClass" :placeholder="t('checkout.name_placeholder')">
                                <span v-if="form.errors.customer_name" class="text-red-500 text-xs mt-1">{{ form.errors.customer_name }}</span>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('checkout.phone') }} *</label>
                                <input v-model="form.customer_phone" type="text" required :class="fieldClass" placeholder="01XXX-XXXXXX">
                                <span v-if="form.errors.customer_phone" class="text-red-500 text-xs mt-1">{{ form.errors.customer_phone }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('checkout.email') }}<span v-if="selectedMethod?.mode === 'redirect'"> *</span></label>
                                <input v-model="form.customer_email" type="email" :required="selectedMethod?.mode === 'redirect'" :class="fieldClass" :placeholder="t('checkout.email_placeholder')">
                                <p class="text-xs mt-1 opacity-70" :class="mutedClass">{{ selectedMethod?.mode === 'redirect' ? t('checkout.email_hint_online') : t('checkout.email_hint') }}</p>
                                <span v-if="form.errors.customer_email" class="text-red-500 text-xs mt-1">{{ form.errors.customer_email }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('checkout.city') }} *</label>
                            <select v-model="form.shipping_city" required :class="fieldClass" @change="updateShipping">
                                <option value="Dhaka">{{ props.shipping_methods.length ? t('checkout.inside_dhaka') : t('checkout.city_dhaka_option', { charge: shippingDhaka }) }}</option>
                                <option value="Outside Dhaka">{{ props.shipping_methods.length ? t('checkout.outside_dhaka') : t('checkout.city_outside_option', { charge: shippingOutside }) }}</option>
                            </select>
                            <span v-if="form.errors.shipping_city" class="text-red-500 text-xs mt-1">{{ form.errors.shipping_city }}</span>
                        </div>

                        <fieldset v-if="methodsForCity(form.shipping_city).length" class="space-y-3">
                            <legend class="text-sm font-semibold" :class="mutedClass">{{ t('checkout.shipping_method') }}</legend>
                            <label
                                v-for="method in methodsForCity(form.shipping_city)"
                                :key="method.id"
                                class="flex items-start gap-3 min-h-11 border p-4 cursor-pointer transition-colors rounded-xl"
                                :class="form.shipping_method_id === method.id ? choiceSelectedClass : choiceIdleClass"
                            >
                                <input v-model="form.shipping_method_id" type="radio" class="mt-1" :class="accentTextClass" :value="method.id" @change="updateShipping">
                                <span class="min-w-0">
                                    <span class="block font-semibold" :class="inkClass">{{ method.name }}</span>
                                    <span class="block text-sm" :class="mutedClass">{{ method.estimate }}</span>
                                </span>
                                <span class="ml-auto font-semibold" :class="inkClass">{{ methodCharge(method) === 0 ? t('checkout.shipping_free') : formatPrice(methodCharge(method)) }}</span>
                            </label>
                            <span v-if="form.errors.shipping_method_id" class="text-red-500 text-xs">{{ form.errors.shipping_method_id }}</span>
                        </fieldset>

                        <div>
                            <label class="block text-sm font-semibold mb-2" :class="mutedClass">{{ t('checkout.full_address') }} *</label>
                            <textarea v-model="form.shipping_address_line" required rows="3" :class="fieldClass" :placeholder="t('checkout.address_placeholder')"></textarea>
                            <span v-if="form.errors.shipping_address_line" class="text-red-500 text-xs mt-1">{{ form.errors.shipping_address_line }}</span>
                        </div>

                        <fieldset class="pt-6 border-t border-black/5 space-y-3">
                            <legend class="text-sm font-semibold" :class="mutedClass">{{ t('checkout.payment_method') }}</legend>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label
                                    v-for="method in methods"
                                    :key="method.key"
                                    class="flex items-start gap-3 min-h-11 border p-4 cursor-pointer transition-colors rounded-xl"
                                    :class="form.payment_method === method.key ? choiceSelectedClass : choiceIdleClass"
                                >
                                    <input
                                        v-model="form.payment_method"
                                        type="radio"
                                        name="payment_method"
                                        class="mt-1 h-4 w-4 shrink-0"
                                        :class="accentTextClass"
                                        style="accent-color: currentColor"
                                        :value="method.key"
                                    >
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold break-words" :class="inkClass">{{ method.label }}</span>
                                        <span class="block text-xs mt-1 break-words" :class="mutedClass">{{ method.description }}</span>
                                        <span v-if="method.account" class="block text-sm font-semibold mt-2 break-all" :class="accentTextClass">{{ method.account }}</span>
                                    </span>
                                </label>
                            </div>
                            <div v-if="selectedMethod?.mode === 'manual'">
                                <label class="block text-sm font-semibold mb-2" :class="mutedClass" for="transaction-reference">{{ t('checkout.transaction_reference') }}</label>
                                <input
                                    id="transaction-reference"
                                    v-model="form.transaction_reference"
                                    type="text"
                                    maxlength="100"
                                    :class="fieldClass"
                                    :placeholder="t('checkout.transaction_reference_placeholder')"
                                >
                                <p class="text-xs mt-1 opacity-70" :class="mutedClass">{{ t('checkout.transaction_reference_hint') }}</p>
                            </div>
                        </fieldset>

                        <div class="pt-6 border-t border-black/5">
                            <div v-if="paymentError" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-semibold flex items-start gap-2">
                                <AlertCircle class="w-5 h-5 shrink-0" />
                                <span class="break-words">{{ paymentError }}</span>
                            </div>
                            <div v-if="form.errors.stock" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-semibold flex items-center gap-2">
                                <AlertCircle class="w-5 h-5 shrink-0" />
                                <span>{{ form.errors.stock }}</span>
                            </div>
                            <div v-else-if="Object.keys(form.errors).some((key) => !['payment', 'payment_method', 'stock'].includes(key))" class="mb-4 p-3 bg-red-50 text-red-500 rounded-xl text-sm font-semibold">
                                {{ t('checkout.form_errors') }}
                            </div>
                            <button type="submit" :disabled="form.processing" :class="[primaryButtonClass, 'w-full py-4 text-sm sm:text-base']">
                                <span>{{ form.processing ? t('checkout.placing') : t('checkout.confirm_order') }}</span>
                                <ArrowRight class="w-5 h-5" />
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="w-full lg:w-1/3">
                    <div :class="[panelClass, 'p-6 sticky top-8']">
                        <h3 class="text-lg font-bold mb-4" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('checkout.order_summary') }}</h3>
                        
                        <div class="space-y-4 mb-6 max-h-60 overflow-y-auto pr-2">
                            <div v-for="item in cartItems" :key="item.cartItemId || item.id" class="flex gap-4">
                                <img :src="item.image" :alt="item.name" class="w-16 h-16 object-cover border border-black/10 bg-black/5">
                                <div class="flex-1">
                                    <h4 class="text-sm font-bold line-clamp-2" :class="inkClass">{{ item.name }}</h4>
                                    <div class="text-sm mt-1" :class="mutedClass">{{ formatPrice(item.price) }} x {{ item.quantity }}</div>
                                </div>
                                <div class="text-sm font-bold" :class="accentTextClass">{{ formatPrice(item.price * item.quantity) }}</div>
                            </div>
                        </div>

                        <div class="mt-4 mb-6">
                            <div v-if="!appliedCoupon">
                                <label class="block text-sm font-medium mb-2" :class="mutedClass">{{ t('checkout.coupon') }}</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none opacity-50">
                                            <Tag class="w-4 h-4" />
                                        </div>
                                        <input
                                            v-model="couponCode"
                                            type="text"
                                            :class="[fieldClass, 'pl-9 uppercase placeholder:normal-case']"
                                            :placeholder="t('checkout.coupon_placeholder')"
                                            @keyup.enter="applyCoupon"
                                        />
                                    </div>
                                    <button
                                        @click="applyCoupon"
                                        type="button"
                                        :disabled="applyingCoupon || !couponCode.trim()"
                                        :class="[primaryButtonClass, 'px-4 py-2.5 text-sm']"
                                    >
                                        {{ applyingCoupon ? t('checkout.applying_short') : t('checkout.apply') }}
                                    </button>
                                </div>
                                <p v-if="couponError" class="mt-2 text-[13px] text-red-500">{{ couponError }}</p>
                            </div>
                            <div v-else class="flex items-center justify-between bg-green-50 border border-green-200 p-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                        <Tag class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="text-[13px] font-medium text-green-800 uppercase">{{ appliedCoupon }}</p>
                                        <p class="text-[12px] text-green-600">{{ t('checkout.coupon_applied_msg') }}</p>
                                    </div>
                                </div>
                                <button @click="removeCoupon" type="button" class="p-1.5 text-green-600 hover:bg-green-100 rounded-full transition-colors">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-black/5 pt-4 space-y-3">
                            <div class="flex justify-between text-sm font-medium" :class="mutedClass">
                                <span>{{ t('checkout.subtotal_items', { count: totalCount }) }}</span>
                                <span>{{ formatPrice(totalAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-medium" :class="mutedClass">
                                <span>{{ t('checkout.shipping') }}</span>
                                <span>{{ formatPrice(shippingCharge) }}</span>
                            </div>
                            <div v-if="discountAmount > 0" class="flex justify-between text-sm font-medium text-green-600">
                                <span>{{ t('checkout.discount') }} ({{ appliedCoupon }})</span>
                                <span>- {{ formatPrice(discountAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold pt-3 border-t border-black/5" :class="inkClass">
                                <span>{{ t('checkout.grand_total') }}</span>
                                <span :class="accentTextClass">{{ formatPrice(grandTotal) }}</span>
                            </div>
                        </div>

                        <div :class="[softPanelClass, 'mt-6 flex items-start gap-3 text-sm p-4']" :style="{ color: 'inherit' }">
                            <Truck v-if="!selectedMethod || selectedMethod.mode === 'cod'" class="w-5 h-5 shrink-0" :class="accentTextClass" />
                            <Wallet v-else class="w-5 h-5 shrink-0" :class="accentTextClass" />
                            <span class="break-words" :class="mutedClass">{{ selectedMethod?.description || t('checkout.cod_note') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
