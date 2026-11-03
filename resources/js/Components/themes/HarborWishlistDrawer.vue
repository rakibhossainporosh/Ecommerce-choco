<script setup>
import { X, Heart, Trash2, ShoppingBag } from 'lucide-vue-next';
import { useCart } from '../../Composables/useCart';
import { useI18n } from '../../Composables/useI18n';

const { isWishlistOpen, wishlistItems, toggleWishlist, addToCart, closeWishlist } = useCart();
const { t } = useI18n();
const formatPrice = (val) => (val ? '৳ ' + Number(val).toLocaleString('en-US') : '');
const itemName = (item) => item.display_name || item.name;
const handleAddToCart = (item) => {
    addToCart(item);
    toggleWishlist(item);
};
</script>

<template>
    <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isWishlistOpen" class="fixed inset-0 bg-[#0f172a]/45 z-50" @click="closeWishlist" />
    </Transition>
    <Transition enter-active-class="transition-transform duration-300" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition-transform duration-200" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
        <div v-if="isWishlistOpen" class="fixed inset-y-0 right-0 max-w-md w-full bg-white z-50 flex flex-col border-l-4 border-[#1d4ed8]">
            <div class="px-4 py-3 bg-[#0f172a] text-white flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#93c5fd]">{{ t('wishlist.title') }}</p>
                    <h3 class="text-sm font-black uppercase tracking-wider">{{ t('cart.items', { count: wishlistItems.length }) }}</h3>
                </div>
                <button type="button" @click="closeWishlist"><X class="w-5 h-5" /></button>
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-[#e7eef8]">
                <div v-if="wishlistItems.length === 0" class="py-16 text-center px-4">
                    <Heart class="w-7 h-7 text-[#6d83a0] mx-auto mb-3" />
                    <p class="text-sm font-bold">{{ t('wishlist.empty') }}</p>
                    <p class="text-xs text-[#6d83a0] mt-1">{{ t('wishlist.empty_hint') }}</p>
                    <button type="button" class="mt-5 h-10 px-5 bg-[#1d4ed8] text-white text-xs font-black uppercase tracking-wider rounded-sm" @click="closeWishlist">{{ t('home.browse_all') }}</button>
                </div>
                <div v-for="item in wishlistItems" :key="item.id" class="p-4 flex gap-3">
                    <img :src="item.image" :alt="itemName(item)" class="w-14 h-14 object-cover border border-[#d5e0ee] rounded-sm" />
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold truncate">{{ itemName(item) }}</h4>
                        <p class="text-sm font-black text-[#1d4ed8] mt-0.5">{{ formatPrice(item.price) }}</p>
                        <div class="mt-2 flex gap-1.5">
                            <button type="button" class="flex-1 h-8 bg-[#1d4ed8] text-white text-[10px] font-black uppercase tracking-wider rounded-sm inline-flex items-center justify-center gap-1" @click="handleAddToCart(item)">
                                <ShoppingBag class="w-3.5 h-3.5" /> {{ t('product.add_to_cart') }}
                            </button>
                            <button type="button" class="h-8 w-8 border border-[#d5e0ee] rounded-sm text-[#6d83a0]" @click="toggleWishlist(item)"><Trash2 class="w-3.5 h-3.5 mx-auto" /></button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="wishlistItems.length" class="p-4 border-t border-[#d5e0ee] bg-[#f4f7fb]">
                <button type="button" class="w-full h-11 border border-[#d5e0ee] bg-white text-[#1d4ed8] text-xs font-black uppercase tracking-[0.1em] rounded-sm" @click="closeWishlist">{{ t('home.browse_all') }}</button>
            </div>
        </div>
    </Transition>
</template>
