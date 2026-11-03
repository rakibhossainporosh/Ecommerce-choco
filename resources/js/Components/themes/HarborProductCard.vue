<script setup>
import { Heart, ShoppingBag, Star } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { useCart } from '../../Composables/useCart';
import { useI18n } from '../../Composables/useI18n';

const props = defineProps({
    product: { type: Object, required: true },
});

const { t } = useI18n();
const { addToCart, toggleWishlist, isInWishlist } = useCart();

const productName = () => props.product.display_name || props.product.name;
const categoryName = () => props.product.category_display_name || props.product.category_name;
const navigateToProduct = () => router.visit('/product/' + props.product.slug);
const formatPrice = (val) => (val ? '৳ ' + Number(val).toLocaleString('en-US') : '');
</script>

<template>
    <article
        class="group cursor-pointer bg-white border border-[#d5e0ee] rounded-sm hover:border-[#1d4ed8] hover:shadow-[0_12px_28px_rgb(29_78_216/0.12)] transition-all duration-200 flex flex-col overflow-hidden"
        @click="navigateToProduct"
    >
        <div class="relative aspect-square bg-[#f4f7fb] border-b border-[#e7eef8] overflow-hidden">
            <img
                :src="product.image"
                :alt="productName()"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
            <div
                v-if="product.is_in_stock === false"
                class="absolute top-2 left-2 px-2 py-0.5 bg-red-600 text-white text-[10px] font-black uppercase tracking-wider rounded-sm"
            >
                {{ t('product.out_of_stock') }}
            </div>
            <div
                v-else-if="product.compare_at_price && product.compare_at_price > product.price"
                class="absolute top-2 left-2 px-2 py-0.5 bg-[#1d4ed8] text-white text-[10px] font-black uppercase tracking-wider rounded-sm"
            >
                {{ t('home.offer_badge') }}
            </div>
            <button
                type="button"
                class="absolute top-2 right-2 w-8 h-8 bg-white/95 border border-[#d5e0ee] rounded-sm flex items-center justify-center shadow-sm"
                :class="isInWishlist(product.id) ? 'text-[#1d4ed8]' : 'text-[#6d83a0] hover:text-[#1d4ed8]'"
                @click.stop="toggleWishlist(product)"
            >
                <Heart class="w-3.5 h-3.5" :class="{ 'fill-[#1d4ed8]': isInWishlist(product.id) }" />
            </button>
        </div>

        <div class="p-3 flex-1 flex flex-col">
            <div class="flex items-center justify-between gap-2 min-h-[1.1rem]">
                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#6d83a0] truncate">{{ categoryName() }}</span>
                <span v-if="product.rating" class="inline-flex items-center gap-0.5 text-[11px] font-bold text-[#0f172a] shrink-0">
                    <Star class="w-3 h-3 fill-[#1d4ed8] text-[#1d4ed8]" />
                    {{ Number(product.rating).toFixed(1) }}
                </span>
            </div>
            <h3 class="mt-1.5 text-sm font-bold text-[#0f172a] line-clamp-2 min-h-[2.5rem] leading-snug group-hover:text-[#1d4ed8]">
                {{ productName() }}
            </h3>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="store-price text-base font-black text-[#1d4ed8]">{{ formatPrice(product.price) }}</span>
                <span
                    v-if="product.compare_at_price && product.compare_at_price > product.price"
                    class="text-xs text-[#6d83a0] line-through"
                >
                    {{ formatPrice(product.compare_at_price) }}
                </span>
            </div>
            <div class="mt-auto pt-3">
                <button
                    v-if="product.is_in_stock !== false"
                    type="button"
                    class="w-full h-10 bg-[#1d4ed8] hover:bg-[#1e40af] text-white text-xs font-black uppercase tracking-[0.06em] rounded-sm inline-flex items-center justify-center gap-1.5 transition-colors"
                    @click.stop="addToCart(product)"
                >
                    <ShoppingBag class="w-3.5 h-3.5" />
                    {{ t('product.add_to_cart') }}
                </button>
                <button
                    v-else
                    type="button"
                    disabled
                    class="w-full h-10 bg-[#e7eef8] text-[#6d83a0] text-xs font-black uppercase tracking-[0.06em] rounded-sm cursor-not-allowed"
                >
                    {{ t('product.out_of_stock') }}
                </button>
            </div>
        </div>
    </article>
</template>
