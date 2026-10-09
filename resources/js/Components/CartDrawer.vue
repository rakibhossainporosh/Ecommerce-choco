<script setup>
import { X, Plus, Minus, Trash2, Info, ShoppingBag } from 'lucide-vue-next';
import { useCart } from '../Composables/useCart';

const { 
    isCartOpen, 
    cartItems, 
    updateQuantity, 
    removeFromCart, 
    totalAmount, 
    totalCount, 
    closeCart 
} = useCart();

const formatPrice = (val) => {
    return '৳ ' + Number(val).toLocaleString('en-US');
};

import { Link } from '@inertiajs/vue3';
</script>

<template>
    <!-- Overlay Backdrop -->
    <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isCartOpen"
            @click="closeCart"
            class="fixed inset-0 bg-black/40 backdrop-blur-xs z-50"
        ></div>
    </Transition>

    <!-- Slide-over Drawer matching screenshot -->
    <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-200 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
    >
        <div
            v-if="isCartOpen"
            class="fixed inset-y-0 right-0 max-w-sm sm:max-w-md w-full bg-white shadow-2xl z-50 flex flex-col justify-between border-l border-[#EAE3D6]"
        >
            <!-- Header -->
            <div class="px-6 py-5 border-b border-[#F0EAE1] flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-bold text-[#1E293B]">কার্ট</h3>
                    <span v-if="totalCount > 0" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-[#FAF3EC] text-[#E86A28]">
                        {{ totalCount }} টি পণ্য
                    </span>
                </div>
                <button
                    @click="closeCart"
                    type="button"
                    class="p-2 text-[#94A3B8] hover:text-[#1E293B] hover:bg-[#FAF7F2] rounded-full transition-colors"
                    title="বন্ধ করুন"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Item List -->
            <div class="flex-1 overflow-y-auto px-6 py-4 divide-y divide-[#F5EFE6]">
                <div v-if="cartItems.length === 0" class="py-16 text-center">
                    <div class="w-16 h-16 rounded-full bg-[#FAF7F2] text-[#94A3B8] flex items-center justify-center mx-auto mb-3">
                        <ShoppingBag class="w-7 h-7" />
                    </div>
                    <p class="text-base font-semibold text-[#1E293B]">কার্টটি এখন খালি</p>
                    <p class="text-sm text-[#64748B] mt-1">পণ্য ব্রাউজ করুন এবং কার্টে যোগ করুন</p>
                </div>

                <div
                    v-for="item in cartItems"
                    :key="item.cartItemId || item.id"
                    class="py-4 flex items-center gap-4 first:pt-0 last:pb-0"
                >
                    <!-- Thumbnail -->
                    <img
                        :src="item.image"
                        :alt="item.name"
                        class="w-14 h-14 rounded-xl object-cover bg-[#F7F3EC] border border-[#EAE3D6] shrink-0"
                    />

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-[#1E293B] truncate">
                            {{ item.name }}
                        </h4>
                        <div class="text-sm font-semibold text-[#E86A28] mt-0.5">
                            {{ formatPrice(item.price) }}
                        </div>

                        <!-- Quantity Selector matching screenshot -->
                        <div class="flex items-center gap-3 mt-2">
                            <div class="inline-flex items-center border border-[#E2D9CD] rounded-lg bg-[#FAF7F2]">
                                <button
                                    @click="updateQuantity(item.cartItemId || item.id, -1)"
                                    type="button"
                                    class="p-1 px-2 text-[#64748B] hover:text-[#1E293B] hover:bg-white rounded-l-lg transition-colors"
                                >
                                    <Minus class="w-3 h-3" />
                                </button>
                                <span class="px-2.5 text-xs font-bold text-[#1E293B]">
                                    {{ item.quantity }}
                                </span>
                                <button
                                    @click="updateQuantity(item.cartItemId || item.id, 1)"
                                    type="button"
                                    class="p-1 px-2 text-[#64748B] hover:text-[#1E293B] hover:bg-white rounded-r-lg transition-colors"
                                >
                                    <Plus class="w-3 h-3" />
                                </button>
                            </div>

                            <button
                                @click="removeFromCart(item.cartItemId || item.id)"
                                type="button"
                                class="text-[#94A3B8] hover:text-rose-500 p-1 transition-colors"
                                title="মুছে ফেলুন"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer & Checkout matching screenshot -->
            <div class="p-6 border-t border-[#F0EAE1] bg-[#FAF8F5]">
                <!-- Subtotal Row -->
                <div class="flex items-center justify-between mb-4">
                    <span class="text-base font-semibold text-[#64748B]">মোট</span>
                    <span class="text-2xl font-bold text-[#1E293B]">
                        {{ formatPrice(totalAmount) }}
                    </span>
                </div>

                <!-- Demo UI Notice Box matching screenshot -->
                <div class="mb-5 p-3 rounded-xl bg-[#F4EFE6] border border-[#E5DDD0] flex items-start gap-2.5 text-[12px] text-[#64748B] leading-relaxed">
                    <Info class="w-4 h-4 text-[#E86A28] shrink-0 mt-0.5" />
                    <span>এটি একটি ডেমো UI। চেকআউট এবং পেমেন্ট সংযুক্ত নয়।</span>
                </div>

                <!-- Checkout Button -->
                <Link
                    href="/checkout"
                    @click="closeCart"
                    class="w-full py-3.5 px-6 bg-[#E86A28] hover:bg-[#D35B1D] text-white font-bold text-base rounded-full shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2"
                >
                    <span>চেকআউট</span>
                </Link>
            </div>
        </div>
    </Transition>
</template>
