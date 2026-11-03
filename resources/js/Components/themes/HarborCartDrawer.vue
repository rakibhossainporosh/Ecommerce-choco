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
    <Teleport to="body">
        <template v-if="isCartOpen">
            <div class="store-drawer fixed inset-0 bg-[#0f172a]/45 z-[80]" @click="closeCart" />
            <div class="store-drawer fixed inset-y-0 right-0 max-w-md w-full bg-white z-[90] flex flex-col border-l-4 border-[#1d4ed8] shadow-2xl">
                <div class="px-4 py-3 bg-[#0f172a] text-white flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#93c5fd]">{{ t('cart.title') }}</p>
                        <h3 class="text-sm font-black uppercase tracking-wider">{{ t('cart.items', { count: totalCount }) }}</h3>
                    </div>
                    <button type="button" class="text-white/80 hover:text-white" @click="closeCart"><X class="w-5 h-5" /></button>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-[#e7eef8]">
                    <div v-if="cartItems.length === 0" class="py-16 text-center px-4">
                        <ShoppingBag class="w-7 h-7 text-[#6d83a0] mx-auto mb-3" />
                        <p class="text-sm font-bold">{{ t('cart.empty') }}</p>
                        <p class="text-xs text-[#6d83a0] mt-1">{{ t('cart.empty_hint') }}</p>
                        <button type="button" class="mt-5 h-10 px-5 bg-[#1d4ed8] text-white text-xs font-black uppercase tracking-wider rounded-sm" @click="closeCart">{{ t('home.browse_all') }}</button>
                    </div>
                    <div v-for="item in cartItems" :key="item.cartItemId || item.id" class="p-4 flex gap-3">
                        <img :src="item.image" :alt="item.name" class="w-14 h-14 object-cover border border-[#d5e0ee] rounded-sm bg-[#f4f7fb]" />
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold truncate">{{ item.name }}</h4>
                            <p class="text-sm font-black text-[#1d4ed8] mt-0.5">{{ formatPrice(item.price) }}</p>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="inline-flex items-center border border-[#d5e0ee] rounded-sm bg-[#f4f7fb]">
                                    <button type="button" class="px-2 py-1" @click="updateQuantity(item.cartItemId || item.id, -1)"><Minus class="w-3 h-3" /></button>
                                    <span class="px-2 text-xs font-black">{{ item.quantity }}</span>
                                    <button type="button" class="px-2 py-1" @click="updateQuantity(item.cartItemId || item.id, 1)"><Plus class="w-3 h-3" /></button>
                                </div>
                                <button type="button" class="text-[#6d83a0] hover:text-rose-500" @click="removeFromCart(item.cartItemId || item.id)"><Trash2 class="w-3.5 h-3.5" /></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-[#d5e0ee] bg-[#f4f7fb]">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-black uppercase tracking-wider text-[#3e516b]">{{ t('cart.total') }}</span>
                        <span class="text-xl font-black text-[#1d4ed8]">{{ formatPrice(totalAmount) }}</span>
                    </div>
                    <div class="mb-3 flex items-center gap-2 text-[11px] text-[#3e516b]">
                        <ShieldCheck class="w-3.5 h-3.5 text-[#1d4ed8]" />
                        <span>{{ t('cart.secure_checkout') }}</span>
                    </div>
                    <Link href="/checkout" class="w-full h-11 bg-[#1d4ed8] hover:bg-[#1e40af] text-white text-xs font-black uppercase tracking-[0.1em] rounded-sm inline-flex items-center justify-center" @click="closeCart">{{ t('cart.checkout') }}</Link>
                </div>
            </div>
        </template>
    </Teleport>
</template>
