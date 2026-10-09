<script setup>
import { Heart, ShoppingBag, Eye } from 'lucide-vue-next';
import { useCart } from '../Composables/useCart';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['quickView']);

const { addToCart, toggleWishlist, isInWishlist } = useCart();

const formatPrice = (val) => {
    if (!val) return '';
    return '৳ ' + Number(val).toLocaleString('en-US');
};
</script>

<template>
    <div class="group bg-white rounded-2xl border border-[#EAE3D6] p-3 sm:p-3.5 flex flex-col justify-between transition-all duration-300 hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] hover:border-[#DCCFC0]">
        <!-- Product Image & Wishlist Button -->
        <div class="relative w-full aspect-square bg-[#F7F3EC] rounded-xl overflow-hidden mb-3.5 flex items-center justify-center">
            <img
                :src="product.image"
                :alt="product.name"
                class="w-full h-full object-cover object-center transition-transform duration-500 group-hover:scale-105"
            />

            <!-- Wishlist Heart Button -->
            <button
                @click.stop="toggleWishlist(product.id)"
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
                <span class="text-[12px] font-medium text-[#94A3B8] uppercase tracking-wider block">
                    {{ product.category_name }}
                </span>

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
                    @click="addToCart(product)"
                    type="button"
                    class="flex-1 py-2 px-3 bg-[#FAF7F2] hover:bg-[#E86A28] text-[#1E293B] hover:text-white border border-[#EAE3D6] hover:border-[#E86A28] rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition-all shadow-2xs"
                >
                    <ShoppingBag class="w-3.5 h-3.5" />
                    <span>কার্টে যোগ করুন</span>
                </button>

                <button
                    @click="$emit('quickView', product)"
                    type="button"
                    class="py-2 px-2.5 bg-white hover:bg-[#FAF7F2] text-[#64748B] hover:text-[#1E293B] border border-[#EAE3D6] rounded-lg text-xs font-medium transition-colors"
                    title="দ্রুত দেখুন"
                >
                    <Eye class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>
    </div>
</template>
