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
        <div v-if="isWishlistOpen" class="fixed inset-0 bg-[#1a2e28]/45 z-50" @click="closeWishlist" />
    </Transition>
    <Transition enter-active-class="transition-transform duration-300" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition-transform duration-200" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
        <div v-if="isWishlistOpen" class="fixed inset-y-0 right-0 max-w-md w-full bg-[#f3efe8] z-50 flex flex-col shadow-2xl">
            <div class="px-5 py-4 border-b border-[#1a2e28]/10 bg-[#1a2e28] text-[#f3faf6] flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-[#9fd6c4] font-semibold">{{ t('wishlist.title') }}</p>
                    <h3 class="mt-0.5 text-lg font-semibold" style="font-family: var(--store-display)">{{ t('cart.items', { count: wishlistItems.length }) }}</h3>
                </div>
                <button type="button" class="w-9 h-9 bg-white/10 text-[#f3faf6] inline-flex items-center justify-center hover:bg-white/20" @click="closeWishlist">
                    <X class="w-4 h-4" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                <div v-if="wishlistItems.length === 0" class="py-16 text-center">
                    <Heart class="w-8 h-8 text-[#8a9a92] mx-auto mb-3" />
                    <p class="font-semibold text-[#1a2e28]" style="font-family: var(--store-display)">{{ t('wishlist.empty') }}</p>
                    <p class="text-sm text-[#5c6f67] mt-1">{{ t('wishlist.empty_hint') }}</p>
                    <button type="button" class="mt-5 h-10 px-5 bg-[#1a2e28] text-white text-[11px] font-semibold uppercase tracking-[0.12em]" @click="closeWishlist">
                        {{ t('home.browse_all') }}
                    </button>
                </div>
                <div v-for="item in wishlistItems" :key="item.id" class="flex gap-3 p-3 border border-[#1a2e28]/10 bg-white">
                    <img :src="item.image" :alt="itemName(item)" class="w-16 h-16 object-cover bg-[#ebe4d8]" />
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold truncate text-sm text-[#1a2e28]">{{ itemName(item) }}</h4>
                        <p class="mt-1 font-semibold text-[#0e6b58] text-sm">{{ formatPrice(item.price) }}</p>
                        <div class="mt-2.5 flex gap-2">
                            <button
                                type="button"
                                class="flex-1 h-9 bg-[#1a2e28] hover:bg-[#0e6b58] text-white text-[10px] font-semibold uppercase tracking-[0.1em] inline-flex items-center justify-center gap-1.5 transition-colors"
                                @click="handleAddToCart(item)"
                            >
                                <ShoppingBag class="w-3.5 h-3.5" /> {{ t('product.add_to_cart') }}
                            </button>
                            <button
                                type="button"
                                class="w-9 h-9 bg-white border border-[#1a2e28]/15 text-[#8a9a92] hover:text-[#1a2e28] inline-flex items-center justify-center"
                                @click="toggleWishlist(item)"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="wishlistItems.length" class="p-5 border-t border-[#1a2e28]/10 bg-white">
                <button
                    type="button"
                    class="w-full h-11 border border-[#1a2e28]/20 text-[#1a2e28] text-[11px] font-semibold uppercase tracking-[0.12em] hover:bg-[#f3efe8] transition-colors"
                    @click="closeWishlist"
                >
                    {{ t('home.browse_all') }}
                </button>
            </div>
        </div>
    </Transition>
</template>
