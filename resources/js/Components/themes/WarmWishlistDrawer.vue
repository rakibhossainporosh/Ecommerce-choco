<script setup>
import { X, Heart, Trash2, ShoppingBag } from 'lucide-vue-next';
import { useCart } from '../../Composables/useCart';
import { useI18n } from '../../Composables/useI18n';

const { 
    isWishlistOpen, 
    wishlistItems, 
    toggleWishlist,
    addToCart,
    closeWishlist 
} = useCart();

const { t } = useI18n();

const formatPrice = (val) => {
    if (!val) return '';
    return '৳ ' + Number(val).toLocaleString('en-US');
};

const itemName = (item) => item.display_name || item.name;
const itemCategory = (item) => item.category_display_name || item.category_name || t('product.general');

const handleAddToCart = (item) => {
    addToCart(item);
    toggleWishlist(item);
};
</script>

<template>
    <Teleport to="body">
        <template v-if="isWishlistOpen">
        <div
            class="store-drawer fixed inset-0 bg-black/40 backdrop-blur-xs z-[80]"
            @click="closeWishlist"
        ></div>
        <div
            class="store-drawer fixed inset-y-0 right-0 max-w-sm sm:max-w-md w-full bg-white shadow-2xl z-[90] flex flex-col justify-between border-l border-[#EAE3D6]"
        >
            <!-- Header -->
            <div class="px-6 py-5 border-b border-[#F0EAE1] flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-bold text-[#1E293B]">{{ t('wishlist.title') }}</h3>
                    <span v-if="wishlistItems.length > 0" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-[#FAF3EC] text-[#E86A28]">
                        {{ t('cart.items', { count: wishlistItems.length }) }}
                    </span>
                </div>
                <button
                    @click="closeWishlist"
                    type="button"
                    class="p-2 text-[#94A3B8] hover:text-[#1E293B] hover:bg-[#FAF7F2] rounded-full transition-colors"
                    :title="t('wishlist.close')"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Item List -->
            <div class="flex-1 overflow-y-auto px-6 py-4 divide-y divide-[#F5EFE6]">
                <div v-if="wishlistItems.length === 0" class="py-16 text-center">
                    <div class="w-16 h-16 rounded-full bg-[#FAF7F2] text-[#94A3B8] flex items-center justify-center mx-auto mb-3">
                        <Heart class="w-7 h-7" />
                    </div>
                    <p class="text-base font-semibold text-[#1E293B]">{{ t('wishlist.empty') }}</p>
                    <p class="text-sm text-[#64748B] mt-1">{{ t('wishlist.empty_hint') }}</p>
                </div>

                <div
                    v-for="item in wishlistItems"
                    :key="item.id"
                    class="py-4 flex items-center gap-4 first:pt-0 last:pb-0"
                >
                    <!-- Thumbnail -->
                    <img
                        :src="item.image"
                        :alt="itemName(item)"
                        class="w-16 h-16 rounded-xl object-cover bg-[#F7F3EC] border border-[#EAE3D6] shrink-0"
                    />

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <span class="text-[10px] font-medium text-[#94A3B8] uppercase tracking-wider block">
                            {{ itemCategory(item) }}
                        </span>
                        <h4 class="text-sm font-bold text-[#1E293B] truncate mt-0.5">
                            {{ itemName(item) }}
                        </h4>
                        
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="text-sm font-semibold text-[#E86A28]">
                                {{ formatPrice(item.price) }}
                            </span>
                            <span
                                v-if="item.compare_at_price && item.compare_at_price > item.price"
                                class="text-xs text-[#94A3B8] line-through font-medium"
                            >
                                {{ formatPrice(item.compare_at_price) }}
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 mt-2">
                            <button
                                @click="handleAddToCart(item)"
                                type="button"
                                class="flex-1 py-1.5 px-3 bg-[#FAF7F2] hover:bg-[#E86A28] text-[#1E293B] hover:text-white border border-[#EAE3D6] hover:border-[#E86A28] rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all shadow-2xs"
                            >
                                <ShoppingBag class="w-3.5 h-3.5" />
                                <span>{{ t('wishlist.move_to_cart') }}</span>
                            </button>

                            <button
                                @click="toggleWishlist(item)"
                                type="button"
                                class="text-[#94A3B8] hover:text-rose-500 p-1.5 border border-transparent hover:border-[#F0EAE1] rounded-lg transition-all"
                                :title="t('wishlist.remove_title')"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        </template>
    </Teleport>
</template>
