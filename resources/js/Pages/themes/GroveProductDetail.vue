<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ShoppingBag, Plus, Minus, Heart, Star, MessageSquare,
    ShieldCheck, Truck, RotateCcw, X, Check, ChevronRight, ZoomIn,
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

const imageFailed = ref(false);
const zoomOpen = ref(false);
const galleryIndex = ref(0);
const galleryTouchX = ref(0);

const onGallerySwipe = (event) => {
    const endX = event.changedTouches?.[0]?.clientX || 0;
    const delta = endX - galleryTouchX.value;
    if (Math.abs(delta) < 40) {
        return;
    }
    stepGallery(delta < 0 ? 1 : -1);
};

watch(activeImage, () => {
    imageFailed.value = false;
    galleryIndex.value = Math.max(0, images.value.findIndex((img) => img === activeImage.value));
});

onMounted(() => {
    track(props.product);
});

const selectGalleryImage = (img, index) => {
    activeImage.value = img;
    galleryIndex.value = index;
    imageFailed.value = false;
};

const stepGallery = (delta) => {
    if (images.value.length < 2) {
        return;
    }
    const next = (galleryIndex.value + delta + images.value.length) % images.value.length;
    selectGalleryImage(images.value[next], next);
};

const displayImage = () => {
    if (imageFailed.value) {
        return props.product.fallback_image || null;
    }

    return activeImage.value;
};

const onMainImageError = () => {
    if (!imageFailed.value && props.product.fallback_image && activeImage.value !== props.product.fallback_image) {
        imageFailed.value = true;
        activeImage.value = props.product.fallback_image;

        return;
    }
    imageFailed.value = true;
};
</script>

<template>
    <div class="min-h-screen bg-[#f3efe8] text-[#1a2e28] flex flex-col">
        <Header />

        <main class="flex-1 pb-24 lg:pb-0">
            <div class="max-w-[88rem] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
                <nav class="mb-8 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-[#8a9a92] overflow-x-auto">
                    <Link href="/" class="hover:text-[#0e6b58]">{{ t('header.home') }}</Link>
                    <ChevronRight class="w-3.5 h-3.5 shrink-0" />
                    <Link :href="'/?category=' + product.category_slug" class="hover:text-[#0e6b58]">{{ productCategory }}</Link>
                    <ChevronRight class="w-3.5 h-3.5 shrink-0" />
                    <span class="text-[#1a2e28] truncate normal-case tracking-normal">{{ productTitle }}</span>
                </nav>

                <OccasionRibbon v-if="occasion" class="mb-8" :kind="occasion.key" :data-occasion="occasion.key">
                    <span>{{ occasion.label }}</span>
                    <span class="opacity-70">·</span>
                    <span>{{ occasion.lines.products }}</span>
                    <OccasionCountdown v-if="occasion.ends_at" :ends-at="occasion.ends_at" />
                </OccasionRibbon>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-start">
                    <div class="lg:col-span-5">
                        <div
                            class="relative overflow-hidden bg-[#ddd6cb] aspect-square max-w-xl mx-auto lg:mx-0 lg:max-w-none group touch-pan-y"
                            @touchstart.passive="galleryTouchX = $event.changedTouches[0]?.clientX || 0"
                            @touchend.passive="onGallerySwipe($event)"
                        >
                            <img
                                v-if="displayImage()"
                                :src="displayImage()"
                                :alt="productTitle"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.03]"
                                @error="onMainImageError"
                            />
                            <div
                                v-else
                                class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#ebe4d8] to-[#ddd6cb]"
                            >
                                <span class="px-6 text-center text-xl font-semibold text-[#1a2e28]/40" style="font-family: var(--store-display)">{{ productTitle }}</span>
                            </div>
                            <button
                                type="button"
                                class="absolute top-3 right-3 w-10 h-10 bg-white/95 text-[#1a2e28] flex items-center justify-center"
                                @click="toggleWishlist(product)"
                            >
                                <Heart class="w-4 h-4" :class="{ 'fill-[#0e6b58] text-[#0e6b58]': isInWishlist(product.id) }" />
                            </button>
                            <button
                                v-if="displayImage()"
                                type="button"
                                class="absolute bottom-3 right-3 h-9 px-3 bg-[#1a2e28]/90 text-white text-[10px] font-semibold uppercase tracking-[0.12em] inline-flex items-center gap-1.5"
                                @click="zoomOpen = true"
                            >
                                <ZoomIn class="w-3.5 h-3.5" /> Zoom
                            </button>
                            <template v-if="images.length > 1">
                                <button type="button" class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 bg-white/90 text-[#1a2e28] hidden sm:inline-flex items-center justify-center" @click="stepGallery(-1)">‹</button>
                                <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 bg-white/90 text-[#1a2e28] hidden sm:inline-flex items-center justify-center" @click="stepGallery(1)">›</button>
                            </template>
                        </div>
                        <div v-if="images.length > 1" class="mt-3 flex gap-2 overflow-x-auto max-w-xl mx-auto lg:mx-0">
                            <button
                                v-for="(img, index) in images"
                                :key="img"
                                type="button"
                                class="w-14 h-14 sm:w-16 sm:h-16 overflow-hidden border shrink-0 bg-[#ebe4d8]"
                                :class="activeImage === img ? 'border-[#1a2e28]' : 'border-transparent opacity-70 hover:opacity-100'"
                                @click="selectGalleryImage(img, index)"
                            >
                                <img :src="img" :alt="productTitle" class="w-full h-full object-cover" @error="$event.target.style.display='none'" />
                            </button>
                        </div>
                    </div>

                    <div class="lg:col-span-7 flex flex-col lg:pt-1">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#0e6b58]">{{ productCategory }}</p>
                        <h1 class="mt-2 text-2xl sm:text-4xl font-semibold tracking-tight leading-[1.35]" style="font-family: var(--store-display)">{{ productTitle }}</h1>
                        <div class="mt-4 flex items-center gap-2">
                            <div class="flex items-center gap-0.5">
                                <Star v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= Math.round(product.rating || 5) ? 'fill-[#0e6b58] text-[#0e6b58]' : 'text-[#d4ccc0]'" />
                            </div>
                            <span class="text-sm font-medium text-[#5c6f67]">{{ Number(product.rating || 5).toFixed(1) }}</span>
                        </div>
                        <div class="mt-6 flex items-end gap-3 pb-6 border-b border-[#1a2e28]/10">
                            <span class="text-3xl font-semibold text-[#1a2e28]">{{ formatPrice(currentPrice) }}</span>
                            <span v-if="currentComparePrice && currentComparePrice > currentPrice" class="text-lg text-[#8a9a92] line-through">{{ formatPrice(currentComparePrice) }}</span>
                        </div>
                        <p class="mt-6 text-[#5c6f67] leading-relaxed">{{ productShortDesc }}</p>

                        <div v-if="product.available_options" class="mt-8 space-y-5">
                            <div v-for="(values, attrName) in product.available_options" :key="attrName">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#8a9a92] mb-2.5">{{ attrName }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="value in values"
                                        :key="value"
                                        type="button"
                                        class="min-w-11 h-11 px-3 text-sm font-medium border transition-colors"
                                        :class="selectedOptions[attrName] === value ? 'bg-[#1a2e28] border-[#1a2e28] text-white' : 'bg-transparent border-[#1a2e28]/20 text-[#1a2e28] hover:border-[#1a2e28]'"
                                        @click="selectedOptions[attrName] = value"
                                    >
                                        {{ value }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center gap-3">
                            <div class="inline-flex items-center border border-[#1a2e28]/20 h-12 bg-white/60">
                                <button type="button" class="w-11 h-full flex items-center justify-center" :disabled="quantity <= 1" @click="decrementQuantity"><Minus class="w-4 h-4" /></button>
                                <span class="w-10 text-center font-semibold">{{ quantity }}</span>
                                <button type="button" class="w-11 h-full flex items-center justify-center" @click="incrementQuantity"><Plus class="w-4 h-4" /></button>
                            </div>
                            <button
                                type="button"
                                class="flex-1 h-12 text-sm font-semibold uppercase tracking-[0.12em] inline-flex items-center justify-center gap-2 transition-colors"
                                :class="isCurrentInStock ? 'bg-[#1a2e28] hover:bg-[#0e6b58] text-white' : 'bg-[#ddd6cb] text-[#8a9a92] cursor-not-allowed'"
                                :disabled="!isCurrentInStock"
                                @click="handleAddToCart"
                            >
                                <ShoppingBag class="w-4 h-4" />
                                {{ isCurrentInStock ? t('product.add_to_cart') : t('product.out_of_stock') }}
                            </button>
                        </div>

                        <div class="mt-10 grid grid-cols-3 gap-4 pt-6 border-t border-[#1a2e28]/10">
                            <div class="text-center sm:text-left">
                                <Truck class="w-4 h-4 text-[#0e6b58] mx-auto sm:mx-0 mb-2" />
                                <p class="text-[11px] font-medium leading-snug text-[#5c6f67]">{{ t('product.perk_fast_delivery') }}</p>
                            </div>
                            <div class="text-center sm:text-left">
                                <ShieldCheck class="w-4 h-4 text-[#0e6b58] mx-auto sm:mx-0 mb-2" />
                                <p class="text-[11px] font-medium leading-snug text-[#5c6f67]">{{ t('product.perk_cod') }}</p>
                            </div>
                            <div class="text-center sm:text-left">
                                <RotateCcw class="w-4 h-4 text-[#0e6b58] mx-auto sm:mx-0 mb-2" />
                                <p class="text-[11px] font-medium leading-snug text-[#5c6f67]">{{ t('product.perk_easy_return') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="mt-14 pt-10 border-t border-[#1a2e28]/10">
                    <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight mb-4 leading-[1.35]" style="font-family: var(--store-display)">{{ t('product.details_heading') }}</h2>
                    <div class="prose max-w-none text-[#5c6f67]" v-html="productDescriptionHtml || productShortDesc" />
                </section>

                <ProductYoutube :video="product.youtube" />

                <section class="mt-10 pt-10 border-t border-[#1a2e28]/10">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight inline-flex items-center gap-2 leading-[1.35]" style="font-family: var(--store-display)">
                                <MessageSquare class="w-5 h-5 text-[#0e6b58]" />
                                {{ t('product.reviews_heading') }}
                            </h2>
                            <p class="text-sm text-[#8a9a92] mt-1">{{ t('product.reviews_subheading') }}</p>
                        </div>
                        <button
                            type="button"
                            class="h-11 px-5 bg-[#1a2e28] text-white text-[11px] font-semibold uppercase tracking-[0.14em] hover:bg-[#0e6b58] transition-colors"
                            @click="showReviewForm = !showReviewForm"
                        >
                            {{ showReviewForm ? t('product.close_review_form') : t('product.write_review_cta') }}
                        </button>
                    </div>

                    <div v-if="reviewSuccess" class="mb-5 px-4 py-3 border border-[#0e6b58]/25 bg-[#e8f3ef] text-[#0e6b58] text-sm font-semibold inline-flex items-center gap-2">
                        <Check class="w-4 h-4" /> {{ t('product.review_thanks') }}
                    </div>

                    <div v-if="showReviewForm" class="mb-8 border border-[#1a2e28]/12 bg-white">
                        <div class="flex items-center justify-between gap-3 px-4 sm:px-5 py-4 border-b border-[#1a2e28]/10">
                            <div>
                                <h3 class="text-lg font-semibold tracking-tight leading-[1.35]" style="font-family: var(--store-display)">{{ t('product.review_form_title') }}</h3>
                                <p class="text-xs text-[#8a9a92] mt-0.5">{{ t('product.review_form_subtitle') }}</p>
                            </div>
                            <button type="button" class="w-9 h-9 inline-flex items-center justify-center text-[#8a9a92] hover:text-[#1a2e28] hover:bg-[#f3efe8]" @click="showReviewForm = false">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <form class="p-4 sm:p-5 space-y-4" @submit.prevent="submitReview">
                            <div class="flex flex-wrap items-center gap-2" @mouseleave="clearHover">
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    type="button"
                                    class="p-0.5"
                                    @mouseenter="setHover(star)"
                                    @click="setRating(star)"
                                >
                                    <Star
                                        class="w-5 h-5"
                                        :class="(hoverRating || reviewForm.rating) >= star ? 'fill-[#0e6b58] text-[#0e6b58]' : 'text-[#d4ccc0]'"
                                    />
                                </button>
                                <span class="text-sm text-[#5c6f67]">{{ ratingLabels[hoverRating || reviewForm.rating] }}</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <input
                                    v-model="reviewForm.reviewer_name"
                                    type="text"
                                    class="w-full h-11 px-3.5 border border-[#1a2e28]/15 bg-[#f3efe8]/60 text-sm outline-none focus:border-[#0e6b58] focus:bg-white"
                                    :placeholder="t('product.review_name_placeholder')"
                                />
                                <input
                                    v-model="reviewForm.reviewer_email"
                                    type="email"
                                    class="w-full h-11 px-3.5 border border-[#1a2e28]/15 bg-[#f3efe8]/60 text-sm outline-none focus:border-[#0e6b58] focus:bg-white"
                                    :placeholder="t('product.review_email_placeholder')"
                                />
                            </div>
                            <input
                                v-model="reviewForm.title"
                                type="text"
                                class="w-full h-11 px-3.5 border border-[#1a2e28]/15 bg-[#f3efe8]/60 text-sm outline-none focus:border-[#0e6b58] focus:bg-white"
                                :placeholder="t('product.review_title_placeholder')"
                            />
                            <textarea
                                v-model="reviewForm.body"
                                rows="3"
                                class="w-full px-3.5 py-3 border border-[#1a2e28]/15 bg-[#f3efe8]/60 text-sm outline-none focus:border-[#0e6b58] focus:bg-white resize-y min-h-[5.5rem]"
                                :placeholder="t('product.review_body_placeholder')"
                            />
                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                <button
                                    type="submit"
                                    class="h-11 px-6 bg-[#1a2e28] hover:bg-[#0e6b58] text-white text-[11px] font-semibold uppercase tracking-[0.14em] disabled:opacity-60 transition-colors"
                                    :disabled="reviewForm.processing"
                                >
                                    {{ reviewForm.processing ? t('product.submitting_review') : t('product.submit_review') }}
                                </button>
                                <button
                                    type="button"
                                    class="h-11 px-4 text-[11px] font-semibold uppercase tracking-[0.12em] text-[#5c6f67] hover:text-[#1a2e28]"
                                    @click="showReviewForm = false"
                                >
                                    {{ t('product.close_review_form') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div v-if="product.reviews?.length" class="space-y-0 divide-y divide-[#1a2e28]/10 border border-[#1a2e28]/10 bg-white">
                        <article v-for="review in product.reviews" :key="review.id" class="p-4 sm:p-5">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <p class="font-semibold text-[#1a2e28]">{{ review.reviewer_name }}</p>
                                <span class="text-xs text-[#8a9a92]">{{ review.created_at }}</span>
                            </div>
                            <div class="flex items-center gap-0.5 mb-2">
                                <Star
                                    v-for="i in 5"
                                    :key="i"
                                    class="w-3.5 h-3.5"
                                    :class="i <= review.rating ? 'fill-[#0e6b58] text-[#0e6b58]' : 'text-[#d4ccc0]'"
                                />
                            </div>
                            <h4 v-if="review.title" class="font-semibold mb-1 text-[#1a2e28]">{{ review.title }}</h4>
                            <p class="text-sm text-[#5c6f67] leading-relaxed">{{ review.body }}</p>
                        </article>
                    </div>
                    <div v-else class="text-center py-12 border border-[#1a2e28]/10 bg-white">
                        <p class="font-semibold leading-[1.35]" style="font-family: var(--store-display)">{{ t('product.no_reviews_title') }}</p>
                        <p class="text-sm text-[#8a9a92] mt-1">{{ t('product.no_reviews_body') }}</p>
                    </div>
                </section>

                <section v-if="relatedProducts.length" class="mt-14 pt-10 border-t border-[#1a2e28]/10">
                    <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight mb-6 leading-[1.35]" style="font-family: var(--store-display)">{{ t('product.related') }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-3 lg:gap-4">
                        <ProductCard v-for="item in relatedProducts" :key="item.id" :product="item" />
                    </div>
                </section>

                <section v-if="recentProducts.length" class="mt-10 pt-10 border-t border-[#1a2e28]/10">
                    <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight mb-6 leading-[1.35]" style="font-family: var(--store-display)">{{ t('product.recently_viewed') }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-3 lg:gap-4">
                        <ProductCard v-for="item in recentProducts" :key="'recent-' + item.id" :product="item" />
                    </div>
                </section>
            </div>
        </main>

        <div class="fixed bottom-0 inset-x-0 z-30 border-t border-[#1a2e28]/10 bg-[#f3efe8]/95 backdrop-blur-sm p-3 lg:px-8">
            <div class="max-w-[88rem] mx-auto flex items-center gap-3">
                <div class="min-w-0 hidden sm:block">
                    <p class="text-xs uppercase tracking-[0.14em] text-[#8a9a92]">{{ productTitle }}</p>
                    <p class="text-base font-semibold text-[#1a2e28] truncate">{{ formatPrice(currentPrice) }}</p>
                </div>
                <div class="min-w-0 sm:hidden">
                    <p class="text-base font-semibold text-[#1a2e28] truncate">{{ formatPrice(currentPrice) }}</p>
                </div>
                <button
                    type="button"
                    class="ml-auto h-11 px-5 text-[11px] font-semibold uppercase tracking-[0.12em] inline-flex items-center justify-center gap-2 shrink-0 transition-colors"
                    :class="isCurrentInStock ? 'bg-[#1a2e28] text-white hover:bg-[#0e6b58]' : 'bg-[#ddd6cb] text-[#8a9a92] cursor-not-allowed'"
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
                v-if="zoomOpen && displayImage()"
                class="fixed inset-0 z-[60] bg-[#1a2e28]/80 flex items-center justify-center p-4"
                @click="zoomOpen = false"
            >
                <button type="button" class="absolute top-4 right-4 w-10 h-10 bg-white text-[#1a2e28] inline-flex items-center justify-center" @click="zoomOpen = false">
                    <X class="w-5 h-5" />
                </button>
                <img :src="displayImage()" :alt="productTitle" class="max-h-[90vh] max-w-full object-contain" @click.stop />
            </div>
        </Teleport>

        <Footer />
    </div>
</template>
