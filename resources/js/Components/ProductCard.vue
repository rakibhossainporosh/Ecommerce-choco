<script setup>
import { Heart, ShoppingBag, Eye, Star } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { useCart } from '../Composables/useCart';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const navigateToProduct = () => {
    router.visit('/product/' + props.product.slug);
};

const { addToCart, toggleWishlist, isInWishlist } = useCart();

const formatPrice = (val) => {
    if (!val) return '';
    return '৳ ' + Number(val).toLocaleString('en-US');
};
</script>

<template>
    <div 
        @click="navigateToProduct"
        class="group bg-white rounded-2xl border border-[#EAE3D6] p-3 sm:p-3.5 flex flex-col justify-between transition-all duration-300 hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] hover:border-[#DCCFC0] cursor-pointer"
    >
        <!-- Product Image & Wishlist Button -->
        <div class="relative w-full aspect-square bg-[#F7F3EC] rounded-xl overflow-hidden mb-3.5 flex items-center justify-center">
            <img
                :src="product.image"
                :alt="product.name"
                class="w-full h-full object-cover object-center transition-transform duration-500 group-hover:scale-105"
            />

            <!-- Badges -->
            <div
                v-if="product.is_in_stock === false"
                class="absolute top-2.5 left-2.5 px-2.5 py-0.5 bg-red-600/90 text-white text-[11px] font-bold rounded-md shadow-xs"
            >
                স্টক শেষ
            </div>
            <div
                v-else-if="product.compare_at_price && product.compare_at_price > product.price"
                class="absolute top-2.5 left-2.5 px-2 py-0.5 bg-[#E86A28] text-white text-[11px] font-bold rounded-md shadow-xs"
            >
                অফার
            </div>

            <!-- Wishlist Heart Button -->
            <button
                @click.stop="toggleWishlist(product)"
                type="button"
                :class="[
                    'absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center transition-all shadow-sm',
                    isInWishlist(product.id)
                        ? 'text-[#E86A28] bg-white scale-110'
                        : 'text-[#94A3B8] hover:text-[#E86A28] hover:bg-white'
                ]"
                title="উইশলিস্টে যুক্ত করুন"
            >
                <Heart
                    class="w-4 h-4 transition-colors"
                    :class="{ 'fill-[#E86A28] text-[#E86A28]': isInWishlist(product.id) }"
                />
            </button>
        </div>

        <!-- Product Meta & Info -->
        <div class="flex-1 flex flex-col justify-between">
            <div>
                <!-- Category Tag -->
                <div class="flex items-center justify-between">
                    <span class="text-[12px] font-medium text-[#94A3B8] uppercase tracking-wider block">
                        {{ product.category_name }}
                    </span>
                    <div v-if="product.rating" class="flex items-center gap-1">
                        <Star class="w-3.5 h-3.5 fill-[#F59E0B] text-[#F59E0B]" />
                        <span class="text-xs font-semibold text-[#1E293B]">{{ Number(product.rating).toFixed(1) }}</span>
                    </div>
                </div>

                <!-- Title -->
                <h3 class="text-[15px] font-bold text-[#1E293B] mt-0.5 line-clamp-1 group-hover:text-[#E86A28] transition-colors">
                    {{ product.name }}
                </h3>

                <!-- Pricing -->
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="text-base font-bold text-[#1E293B]">
                        {{ formatPrice(product.price) }}
                    </span>
                    <span
                        v-if="product.compare_at_price && product.compare_at_price > product.price"
                        class="text-xs text-[#94A3B8] line-through font-medium"
                    >
                        {{ formatPrice(product.compare_at_price) }}
                    </span>
                </div>
            </div>

            <!-- Action Buttons matching screenshot -->
            <div class="mt-3.5 pt-2 border-t border-[#F1EBE1] flex items-center gap-2">
                <button
                    v-if="product.is_in_stock !== false"
                    @click.stop="addToCart(product)"
                    type="button"
                    class="flex-1 py-2 px-3 bg-[#FAF7F2] hover:bg-[#E86A28] text-[#1E293B] hover:text-white border border-[#EAE3D6] hover:border-[#E86A28] rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all shadow-2xs"
                >
                    <ShoppingBag class="w-3.5 h-3.5" />
                    <span>কার্টে যোগ করুন</span>
                </button>
                <button
                    v-else
                    disabled
                    type="button"
                    class="flex-1 py-2 px-3 bg-gray-100 text-gray-400 border border-gray-200 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 cursor-not-allowed"
                >
                    <span>স্টক শেষ</span>
                </button>

                <button
                    @click.stop="navigateToProduct"
                    type="button"
                    class="py-2 px-2.5 bg-white hover:bg-[#FAF7F2] text-[#64748B] hover:text-[#1E293B] border border-[#EAE3D6] rounded-lg text-xs font-medium transition-colors"
                    title="বিস্তারিত দেখুন"
                >
                    <Eye class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>
    </div>
</template>
