<script setup>
import { ref } from 'vue';
import { Heart, ShoppingBag } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { useCart } from '../../Composables/useCart';
import { useI18n } from '../../Composables/useI18n';

const props = defineProps({
    product: { type: Object, required: true },
    featured: { type: Boolean, default: false },
});

const { t } = useI18n();
const { addToCart, toggleWishlist, isInWishlist } = useCart();
const imageFailed = ref(false);
const usingFallback = ref(false);
const imageSrc = ref(props.product.image);

const productName = () => props.product.display_name || props.product.name;
const categoryName = () => props.product.category_display_name || props.product.category_name;
const navigateToProduct = () => router.visit('/product/' + props.product.slug);
const formatPrice = (val) => (val ? '৳ ' + Number(val).toLocaleString('en-US') : '');

const onImageError = () => {
    if (!usingFallback.value && props.product.fallback_image && imageSrc.value !== props.product.fallback_image) {
        usingFallback.value = true;
        imageSrc.value = props.product.fallback_image;

        return;
    }
    imageFailed.value = true;
};
</script>

<template>
    <article
        class="group cursor-pointer flex flex-col bg-white border border-[#e4ddd2] hover:border-[#1a2e28]/25 hover:shadow-[0_10px_28px_rgb(26_46_40/0.08)] transition-all duration-300"
        @click="navigateToProduct"
    >
        <div class="relative aspect-square bg-[#ebe4d8] overflow-hidden">
            <img
                v-if="!imageFailed && imageSrc"
                :src="imageSrc"
                :alt="productName()"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.03]"
                @error="onImageError"
            />
            <div
                v-else
                class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#ebe4d8] to-[#ddd6cb]"
            >
                <span
                    class="px-3 text-center text-sm font-semibold text-[#1a2e28]/45 leading-snug line-clamp-3"
                    style="font-family: var(--store-display)"
                >{{ productName() }}</span>
            </div>

            <div
                v-if="product.is_in_stock === false"
                class="absolute top-2.5 left-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-white bg-[#1a2e28] px-2 py-1"
            >
                {{ t('product.out_of_stock') }}
            </div>
            <div
                v-else-if="product.compare_at_price && product.compare_at_price > product.price"
                class="absolute top-2.5 left-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-[#1a2e28] bg-[#9fd6c4] px-2 py-1"
            >
                {{ t('home.offer_badge') }}
            </div>

            <button
                type="button"
                class="absolute top-2.5 right-2.5 w-8 h-8 bg-white/95 text-[#1a2e28] flex items-center justify-center shadow-sm hover:text-[#0e6b58]"
                :class="{ 'text-[#0e6b58]': isInWishlist(product.id) }"
                @click.stop="toggleWishlist(product)"
            >
                <Heart class="w-3.5 h-3.5" :class="{ 'fill-current': isInWishlist(product.id) }" />
            </button>

            <div class="absolute inset-x-0 bottom-0 p-2.5 translate-y-full group-hover:translate-y-0 transition-transform duration-300 hidden sm:block">
                <button
                    v-if="product.is_in_stock !== false"
                    type="button"
                    class="w-full h-10 bg-[#1a2e28] hover:bg-[#0e6b58] text-white text-[11px] font-semibold uppercase tracking-[0.1em] inline-flex items-center justify-center gap-2"
                    @click.stop="addToCart(product)"
                >
                    <ShoppingBag class="w-3.5 h-3.5" />
                    {{ t('product.add_to_cart') }}
                </button>
            </div>
        </div>

        <div class="p-2.5 sm:p-3 flex-1 flex flex-col">
            <p class="text-[10px] uppercase tracking-[0.14em] text-[#8a9a92] font-semibold truncate">{{ categoryName() }}</p>
            <h3
                class="mt-1 text-[13px] sm:text-sm font-semibold text-[#1a2e28] leading-[1.45] line-clamp-2 min-h-[2.6rem] group-hover:text-[#0e6b58] transition-colors"
                style="font-family: var(--store-display)"
            >
                {{ productName() }}
            </h3>
            <div class="mt-auto pt-2 flex items-center justify-between gap-2">
                <div class="flex items-baseline gap-1.5 min-w-0">
                    <span class="store-price text-sm font-semibold text-[#1a2e28]">{{ formatPrice(product.price) }}</span>
                    <span
                        v-if="product.compare_at_price && product.compare_at_price > product.price"
                        class="text-[11px] text-[#8a9a92] line-through truncate"
                    >
                        {{ formatPrice(product.compare_at_price) }}
                    </span>
                </div>
            </div>
            <button
                v-if="product.is_in_stock !== false"
                type="button"
                class="mt-2.5 w-full h-8 bg-[#1a2e28] text-white text-[10px] font-semibold uppercase tracking-[0.1em] inline-flex items-center justify-center gap-1.5 sm:hidden"
                @click.stop="addToCart(product)"
            >
                <ShoppingBag class="w-3.5 h-3.5" />
                {{ t('product.add_to_cart') }}
            </button>
        </div>
    </article>
</template>
