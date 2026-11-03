<script setup>
import { X, Plus, Minus, Trash2, ShieldCheck, ShoppingBag } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { useCart } from '../../Composables/useCart';
import { useI18n } from '../../Composables/useI18n';

const { isCartOpen, cartItems, updateQuantity, removeFromCart, totalAmount, totalCount, closeCart } = useCart();
const { t } = useI18n();
const formatPrice = (val) => '৳ ' + Number(val).toLocaleString('en-US');
</script>

<template>
    <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isCartOpen" class="fixed inset-0 bg-[#1a2e28]/45 z-50" @click="closeCart" />
    </Transition>

    <Transition enter-active-class="transition-transform duration-300" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition-transform duration-200" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
        <div v-if="isCartOpen" class="fixed inset-y-0 right-0 max-w-md w-full bg-[#f3efe8] z-50 flex flex-col shadow-2xl">
            <div class="px-5 py-4 border-b border-[#1a2e28]/10 bg-[#1a2e28] text-[#f3faf6] flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-[#9fd6c4] font-semibold">{{ t('cart.title') }}</p>
                    <h3 class="mt-0.5 text-lg font-semibold" style="font-family: var(--store-display)">{{ t('cart.items', { count: totalCount }) }}</h3>
                </div>
                <button type="button" class="w-9 h-9 bg-white/10 text-[#f3faf6] inline-flex items-center justify-center hover:bg-white/20" @click="closeCart">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                <div v-if="cartItems.length === 0" class="py-16 text-center">
                    <ShoppingBag class="w-8 h-8 text-[#8a9a92] mx-auto mb-3" />
                    <p class="font-semibold text-[#1a2e28]" style="font-family: var(--store-display)">{{ t('cart.empty') }}</p>
                    <p class="text-sm text-[#5c6f67] mt-1">{{ t('cart.empty_hint') }}</p>
                    <button type="button" class="mt-5 h-10 px-5 bg-[#1a2e28] text-white text-[11px] font-semibold uppercase tracking-[0.12em]" @click="closeCart">
                        {{ t('home.browse_all') }}
                    </button>
                </div>
                <div v-for="item in cartItems" :key="item.cartItemId || item.id" class="flex gap-3 p-3 border border-[#1a2e28]/10 bg-white">
                    <img :src="item.image" :alt="item.name" class="w-16 h-16 object-cover bg-[#ebe4d8]" />
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-[#1a2e28] truncate text-sm">{{ item.name }}</h4>
                        <p class="mt-1 text-[#0e6b58] font-semibold text-sm">{{ formatPrice(item.price) }}</p>
                        <div class="mt-2.5 flex items-center gap-2">
                            <div class="inline-flex items-center border border-[#1a2e28]/15 bg-[#f3efe8]">
                                <button type="button" class="w-8 h-8 inline-flex items-center justify-center" @click="updateQuantity(item.cartItemId || item.id, -1)">
                                    <Minus class="w-3 h-3" />
                                </button>
                                <span class="w-7 text-center text-xs font-semibold">{{ item.quantity }}</span>
                                <button type="button" class="w-8 h-8 inline-flex items-center justify-center" @click="updateQuantity(item.cartItemId || item.id, 1)">
                                    <Plus class="w-3 h-3" />
                                </button>
                            </div>
                            <button type="button" class="text-[#8a9a92] hover:text-[#1a2e28]" @click="removeFromCart(item.cartItemId || item.id)">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5 border-t border-[#1a2e28]/10 bg-white">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[#5c6f67] font-medium">{{ t('cart.total') }}</span>
                    <span class="text-xl font-semibold text-[#1a2e28]">{{ formatPrice(totalAmount) }}</span>
                </div>
                <div class="mb-4 flex items-center gap-2 text-xs text-[#5c6f67]">
                    <ShieldCheck class="w-4 h-4 text-[#0e6b58]" />
                    <span>{{ t('cart.secure_checkout') }}</span>
                </div>
                <Link
                    href="/checkout"
                    class="w-full h-11 bg-[#1a2e28] hover:bg-[#0e6b58] text-white text-[11px] font-semibold uppercase tracking-[0.14em] inline-flex items-center justify-center transition-colors"
                    @click="closeCart"
                >
                    {{ t('cart.checkout') }}
                </Link>
            </div>
        </div>
    </Transition>
</template>
