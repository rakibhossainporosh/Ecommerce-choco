<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ShoppingBag, Plus, Minus, Heart, Star, MessageSquare,
    ShieldCheck, Truck, RotateCcw, ChevronRight, X, Check,
} from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import OccasionRibbon from '../../Components/OccasionRibbon.vue';
import OccasionCountdown from '../../Components/OccasionCountdown.vue';
import ProductCard from '../../Components/ProductCard.vue';
import ProductYoutube from '../../Components/ProductYoutube.vue';
import { useProductDetailPage } from '../../Composables/useProductDetailPage';
import { useRecentlyViewed } from '../../Composables/useRecentlyViewed';

const props = defineProps({
    product: { type: Object, required: true },
    relatedProducts: { type: Array, default: () => [] },
});

const {
    occasion, t, productTitle, productCategory, productShortDesc, productDescriptionHtml,
    quantity, activeImage, showReviewForm, hoverRating, images, selectedOptions,
    currentPrice, currentComparePrice, isCurrentInStock, formatPrice, handleAddToCart,
    incrementQuantity, decrementQuantity, reviewForm, reviewSuccess, ratingLabels,
    setRating, setHover, clearHover, submitReview, toggleWishlist, isInWishlist,
} = useProductDetailPage(props);

const { track, othersThan } = useRecentlyViewed();
const recentProducts = computed(() => othersThan(props.product.id));

onMounted(() => {
    track(props.product);
});

const imageFailed = ref(false);
const zoomOpen = ref(false);
const galleryTouchX = ref(0);

watch(activeImage, () => {
    imageFailed.value = false;
});

const onMainImageError = () => {
    if (!imageFailed.value && props.product.fallback_image && activeImage.value !== props.product.fallback_image) {
        activeImage.value = props.product.fallback_image;

        return;
    }
    imageFailed.value = true;
};

const activeImageIndex = computed(() => {
    const index = images.value.findIndex((img) => img === activeImage.value);

    return index >= 0 ? index : 0;
});

const stepGallery = (delta) => {
    if (images.value.length < 2) {
        return;
    }

    const next = (activeImageIndex.value + delta + images.value.length) % images.value.length;
    activeImage.value = images.value[next];
};

const onGallerySwipe = (event) => {
    const endX = event.changedTouches?.[0]?.clientX || 0;
    const delta = endX - galleryTouchX.value;

    if (Math.abs(delta) < 40) {
        return;
    }

    stepGallery(delta < 0 ? 1 : -1);
};
</script>

<template>
    <div class="min-h-screen bg-[#f4f7fb] text-[#0f172a] flex flex-col">
        <Header />

        <main class="flex-1 pb-24 lg:pb-0">
            <div class="bg-white border-b border-[#d5e0ee]">
                <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.08em] text-[#6d83a0] overflow-x-auto">
                    <Link href="/" class="hover:text-[#1d4ed8]">{{ t('header.home') }}</Link>
                    <ChevronRight class="w-3 h-3" />
                    <Link :href="'/?category=' + product.category_slug" class="hover:text-[#1d4ed8]">{{ productCategory }}</Link>
                    <ChevronRight class="w-3 h-3" />
                    <span class="text-[#0f172a] truncate">{{ productTitle }}</span>
                </div>
            </div>

            <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                <OccasionRibbon v-if="occasion" class="mb-4" :kind="occasion.key" :data-occasion="occasion.key">
                    <span>{{ occasion.label }}</span>
                    <span>·</span>
                    <span>{{ occasion.lines.products }}</span>
                    <OccasionCountdown v-if="occasion.ends_at" :ends-at="occasion.ends_at" />
                </OccasionRibbon>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-5 bg-white border border-[#d5e0ee] rounded-sm p-3">
                        <div
                            class="relative aspect-square bg-[#f4f7fb] border border-[#e7eef8] rounded-sm overflow-hidden group touch-pan-y cursor-zoom-in"
                            role="button"
                            tabindex="0"
                            @click="!imageFailed && (zoomOpen = true)"
                            @keydown.enter.prevent="!imageFailed && (zoomOpen = true)"
                            @touchstart.passive="galleryTouchX = $event.changedTouches[0]?.clientX || 0"
                            @touchend.passive="onGallerySwipe($event)"
                        >
                            <img
                                v-if="!imageFailed"
                                :src="activeImage"
                                :alt="productTitle"
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.03] pointer-events-none"
                                @error="onMainImageError"
                            />
                            <div
                                v-else
                                class="absolute inset-0 flex items-center justify-center text-sm font-bold text-[#6d83a0] px-4 text-center"
                            >
                                {{ productTitle }}
                            </div>
                            <template v-if="images.length > 1 && !imageFailed">
                                <button type="button" class="absolute left-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/95 text-[#0f172a] hidden sm:inline-flex items-center justify-center rounded-sm shadow-sm" @click.stop="stepGallery(-1)">‹</button>
                                <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/95 text-[#0f172a] hidden sm:inline-flex items-center justify-center rounded-sm shadow-sm" @click.stop="stepGallery(1)">›</button>
                            </template>
                        </div>
                        <div v-if="images.length > 1" class="mt-3 flex gap-2 overflow-x-auto">
                            <button
                                v-for="img in images"
                                :key="img"
                                type="button"
                                class="w-16 h-16 border rounded-sm overflow-hidden shrink-0"
                                :class="activeImage === img ? 'border-[#1d4ed8]' : 'border-[#d5e0ee] opacity-70'"
                                @click="activeImage = img"
                            >
                                <img :src="img" :alt="productTitle" class="w-full h-full object-cover" />
                            </button>
                        </div>
                    </div>

                    <div class="lg:col-span-7 bg-white border border-[#d5e0ee] rounded-sm p-4 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#1d4ed8]">{{ productCategory }}</p>
                                <h1 class="mt-2 text-2xl sm:text-3xl font-black tracking-tight">{{ productTitle }}</h1>
                            </div>
                            <button type="button" class="h-10 w-10 border border-[#d5e0ee] rounded-sm flex items-center justify-center" :class="isInWishlist(product.id) ? 'text-[#1d4ed8]' : 'text-[#6d83a0]'" @click="toggleWishlist(product)">
                                <Heart class="w-4 h-4" :class="{ 'fill-[#1d4ed8]': isInWishlist(product.id) }" />
                            </button>
                        </div>

                        <div class="mt-3 flex items-center gap-2 text-sm">
                            <div class="inline-flex items-center gap-1 px-2 py-1 bg-[#f4f7fb] border border-[#d5e0ee] rounded-sm font-bold">
                                <Star class="w-3.5 h-3.5 fill-[#1d4ed8] text-[#1d4ed8]" />
                                {{ Number(product.rating || 5).toFixed(1) }}
                            </div>
                            <span class="text-[#6d83a0] text-xs font-semibold uppercase tracking-wider">{{ t('product.verified_reviews', { count: product.reviews_count || product.reviews?.length || 0 }) }}</span>
                        </div>

                        <div class="mt-5 flex items-baseline gap-3 border-y border-dashed border-[#d5e0ee] py-4">
                            <span class="text-3xl font-black text-[#1d4ed8]">{{ formatPrice(currentPrice) }}</span>
                            <span v-if="currentComparePrice && currentComparePrice > currentPrice" class="text-base text-[#6d83a0] line-through">{{ formatPrice(currentComparePrice) }}</span>
                            <span v-if="isCurrentInStock" class="ml-auto text-[10px] font-black uppercase tracking-wider text-green-700 bg-green-50 px-2 py-1 rounded-sm">{{ t('product.in_stock_badge') }}</span>
                        </div>

                        <p class="mt-4 text-sm text-[#3e516b] leading-relaxed">{{ productShortDesc }}</p>

                        <div v-if="product.available_options" class="mt-5 space-y-4">
                            <div v-for="(values, attrName) in product.available_options" :key="attrName">
                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#6d83a0] mb-2">{{ attrName }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="value in values"
                                        :key="value"
                                        type="button"
                                        class="h-9 px-3 text-xs font-bold border rounded-sm"
                                        :class="selectedOptions[attrName] === value ? 'bg-[#1d4ed8] border-[#1d4ed8] text-white' : 'bg-[#f4f7fb] border-[#d5e0ee] text-[#3e516b]'"
                                        @click="selectedOptions[attrName] = value"
                                    >
                                        {{ value }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col sm:flex-row gap-2">
                            <div class="inline-flex items-center h-11 border border-[#d5e0ee] rounded-sm bg-[#f4f7fb]">
                                <button type="button" class="w-10 h-full flex items-center justify-center" :disabled="quantity <= 1" @click="decrementQuantity"><Minus class="w-4 h-4" /></button>
                                <span class="w-10 text-center font-black">{{ quantity }}</span>
                                <button type="button" class="w-10 h-full flex items-center justify-center" @click="incrementQuantity"><Plus class="w-4 h-4" /></button>
                            </div>
                            <button
                                type="button"
                                class="flex-1 h-11 rounded-sm text-xs font-black uppercase tracking-[0.1em] inline-flex items-center justify-center gap-2"
                                :class="isCurrentInStock ? 'bg-[#1d4ed8] hover:bg-[#1e40af] text-white' : 'bg-[#e7eef8] text-[#6d83a0] cursor-not-allowed'"
                                :disabled="!isCurrentInStock"
                                @click="handleAddToCart"
                            >
                                <ShoppingBag class="w-4 h-4" />
                                {{ isCurrentInStock ? t('product.add_to_cart') : t('product.out_of_stock') }}
                            </button>
                        </div>

                        <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                            <div class="border border-[#d5e0ee] rounded-sm p-2.5 bg-[#f8fafc]">
                                <Truck class="w-4 h-4 text-[#1d4ed8] mx-auto mb-1" />
                                <p class="text-[10px] font-bold uppercase tracking-wide leading-snug">{{ t('product.perk_fast_delivery') }}</p>
                            </div>
                            <div class="border border-[#d5e0ee] rounded-sm p-2.5 bg-[#f8fafc]">
                                <ShieldCheck class="w-4 h-4 text-[#1d4ed8] mx-auto mb-1" />
                                <p class="text-[10px] font-bold uppercase tracking-wide leading-snug">{{ t('product.perk_cod') }}</p>
                            </div>
                            <div class="border border-[#d5e0ee] rounded-sm p-2.5 bg-[#f8fafc]">
                                <RotateCcw class="w-4 h-4 text-[#1d4ed8] mx-auto mb-1" />
                                <p class="text-[10px] font-bold uppercase tracking-wide leading-snug">{{ t('product.perk_easy_return') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="mt-4 bg-white border border-[#d5e0ee] rounded-sm p-5 sm:p-6">
                    <h2 class="text-xs font-black uppercase tracking-[0.14em] mb-3">{{ t('product.details_heading') }}</h2>
                    <div class="text-sm text-[#3e516b] leading-relaxed" v-html="productDescriptionHtml || productShortDesc" />
                </section>

                <ProductYoutube :video="product.youtube" />

                <section class="mt-4 bg-white border border-[#d5e0ee] rounded-sm p-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#e7eef8] pb-4 mb-4">
                        <h2 class="text-xs font-black uppercase tracking-[0.14em] inline-flex items-center gap-2">
                            <MessageSquare class="w-4 h-4 text-[#1d4ed8]" />
                            {{ t('product.reviews_heading') }}
                        </h2>
                        <button type="button" class="h-9 px-4 bg-[#1d4ed8] text-white text-[10px] font-black uppercase tracking-[0.1em] rounded-sm" @click="showReviewForm = true">{{ t('product.write_review_cta') }}</button>
                    </div>

                    <div v-if="reviewSuccess" class="mb-4 p-3 border border-green-200 bg-green-50 text-green-700 text-sm font-semibold inline-flex items-center gap-2 rounded-sm">
                        <Check class="w-4 h-4" /> {{ t('product.review_thanks') }}
                    </div>

                    <div v-if="showReviewForm" class="mb-6 p-4 border border-[#1d4ed8]/30 rounded-sm bg-[#f4f7fb]">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-black uppercase tracking-wider">{{ t('product.review_form_title') }}</h3>
                            <button type="button" @click="showReviewForm = false"><X class="w-4 h-4" /></button>
                        </div>
                        <div class="flex items-center gap-1 mb-3" @mouseleave="clearHover">
                            <button v-for="star in 5" :key="star" type="button" @mouseenter="setHover(star)" @click="setRating(star)">
                                <Star class="w-5 h-5" :class="(hoverRating || reviewForm.rating) >= star ? 'fill-[#1d4ed8] text-[#1d4ed8]' : 'text-[#d5e0ee]'" />
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                            <input v-model="reviewForm.reviewer_name" type="text" class="h-10 px-3 border border-[#d5e0ee] rounded-sm text-sm outline-none focus:border-[#1d4ed8]" :placeholder="t('product.review_name_placeholder')" />
                            <input v-model="reviewForm.reviewer_email" type="email" class="h-10 px-3 border border-[#d5e0ee] rounded-sm text-sm outline-none focus:border-[#1d4ed8]" :placeholder="t('product.review_email_placeholder')" />
                        </div>
                        <input v-model="reviewForm.title" type="text" class="w-full h-10 mb-2 px-3 border border-[#d5e0ee] rounded-sm text-sm outline-none focus:border-[#1d4ed8]" :placeholder="t('product.review_title_placeholder')" />
                        <textarea v-model="reviewForm.body" rows="3" class="w-full mb-3 px-3 py-2 border border-[#d5e0ee] rounded-sm text-sm outline-none focus:border-[#1d4ed8]" :placeholder="t('product.review_body_placeholder')" />
                        <button type="button" class="h-10 px-5 bg-[#1d4ed8] text-white text-[10px] font-black uppercase tracking-[0.1em] rounded-sm" :disabled="reviewForm.processing" @click="submitReview">{{ t('product.submit_review') }}</button>
                    </div>

                    <div v-if="product.reviews?.length" class="divide-y divide-[#e7eef8]">
                        <article v-for="review in product.reviews" :key="review.id" class="py-4">
                            <div class="flex items-center justify-between gap-3 mb-1">
                                <p class="text-sm font-bold">{{ review.reviewer_name }}</p>
                                <span class="text-[11px] text-[#6d83a0]">{{ review.created_at }}</span>
                            </div>
                            <div class="flex items-center gap-0.5 mb-2">
                                <Star v-for="i in 5" :key="i" class="w-3 h-3" :class="i <= review.rating ? 'fill-[#1d4ed8] text-[#1d4ed8]' : 'text-[#d5e0ee]'" />
                            </div>
                            <h4 v-if="review.title" class="text-sm font-bold mb-1">{{ review.title }}</h4>
                            <p class="text-sm text-[#3e516b]">{{ review.body }}</p>
                        </article>
                    </div>
                    <div v-else class="py-8 text-center">
                        <p class="text-sm font-bold">{{ t('product.no_reviews_title') }}</p>
                        <p class="text-xs text-[#6d83a0] mt-1">{{ t('product.no_reviews_body') }}</p>
                    </div>
                </section>

                <section v-if="relatedProducts.length" class="mt-4 bg-white border border-[#d5e0ee] rounded-sm p-5 sm:p-6">
                    <h2 class="text-xs font-black uppercase tracking-[0.14em] mb-4">{{ t('product.related') }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
                        <ProductCard v-for="item in relatedProducts" :key="item.id" :product="item" />
                    </div>
                </section>

                <section v-if="recentProducts.length" class="mt-4 bg-white border border-[#d5e0ee] rounded-sm p-5 sm:p-6">
                    <h2 class="text-xs font-black uppercase tracking-[0.14em] mb-4">{{ t('product.recently_viewed') }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
                        <ProductCard v-for="item in recentProducts" :key="'recent-' + item.id" :product="item" />
                    </div>
                </section>
            </div>
        </main>

        <div class="lg:hidden fixed bottom-[var(--store-mobile-nav-offset)] inset-x-0 z-30 border-t border-[#d5e0ee] bg-white/95 backdrop-blur-sm p-3 shadow-[0_-8px_24px_rgb(15_23_42/0.08)]">
            <div class="flex items-center gap-2">
                <div class="min-w-0">
                    <p class="text-sm font-black text-[#1d4ed8] truncate">{{ formatPrice(currentPrice) }}</p>
                </div>
                <button
                    type="button"
                    class="ml-auto h-11 px-5 rounded-sm text-xs font-black uppercase tracking-[0.08em] inline-flex items-center justify-center gap-2 shrink-0"
                    :class="isCurrentInStock ? 'bg-[#1d4ed8] text-white' : 'bg-[#e7eef8] text-[#6d83a0] cursor-not-allowed'"
                    :disabled="!isCurrentInStock"
                    @click="handleAddToCart"
                >
                    <ShoppingBag class="w-4 h-4" />
                    {{ isCurrentInStock ? t('product.add_to_cart') : t('product.out_of_stock') }}
                </button>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="zoomOpen && activeImage && !imageFailed"
                class="fixed inset-0 z-[60] bg-[#0f172a]/80 flex items-center justify-center p-4"
                @click="zoomOpen = false"
            >
                <button type="button" class="absolute top-4 right-4 w-10 h-10 bg-white text-[#0f172a] rounded-sm inline-flex items-center justify-center" @click="zoomOpen = false">
                    <X class="w-5 h-5" />
                </button>
                <img :src="activeImage" :alt="productTitle" class="max-h-[90vh] max-w-full object-contain" @click.stop />
            </div>
        </Teleport>

        <Footer />
    </div>
</template>
