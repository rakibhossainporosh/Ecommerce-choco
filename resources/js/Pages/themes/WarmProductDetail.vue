<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ShoppingBag, Plus, Minus, Heart, ChevronRight, Star,
    CheckCircle2, MessageSquare, ShieldCheck, Truck, RotateCcw,
    ThumbsUp, Check, X, ZoomIn,
} from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import OccasionRibbon from '../../Components/OccasionRibbon.vue';
import OccasionCountdown from '../../Components/OccasionCountdown.vue';
import ProductCard from '../../Components/ProductCard.vue';
import { useCart } from '../../Composables/useCart';
import { useI18n } from '../../Composables/useI18n';
import { useRecentlyViewed } from '../../Composables/useRecentlyViewed';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    relatedProducts: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const occasion = computed(() => page.props.site_settings?.occasion || null);
const { t, tm } = useI18n();
const pageShellClass = 'min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col';
const { addToCart, toggleWishlist, isInWishlist } = useCart();

const productTitle = computed(() => props.product.display_name || props.product.name);
const productCategory = computed(() => props.product.category_display_name || props.product.category_name);
const productShortDesc = computed(() => props.product.display_short_description || props.product.short_description || t('product.fallback_short'));
const productDescriptionHtml = computed(() => props.product.display_description || props.product.description);
const quantity = ref(1);
const activeImage = ref(props.product.image);
const showReviewForm = ref(false);
const hoverRating = ref(0);
const zoomOpen = ref(false);
const galleryTouchX = ref(0);

const { track, othersThan } = useRecentlyViewed();
const recentProducts = computed(() => othersThan(props.product.id));

onMounted(() => {
    track(props.product);
});

const images = computed(() => {
    if (props.product.images && props.product.images.length > 0) {
        return props.product.images;
    }
    return [props.product.image];
});

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

const selectedOptions = ref({});
if (props.product.available_options) {
    for (const [attrName, values] of Object.entries(props.product.available_options)) {
        if (values.length > 0) {
            selectedOptions.value[attrName] = values[0];
        }
    }
}

const currentVariant = computed(() => {
    if (!props.product.variants || props.product.variants.length === 0) return null;
    
    return props.product.variants.find(variant => {
        return Object.entries(selectedOptions.value).every(([attrName, value]) => {
            return variant.options && variant.options[attrName] === value;
        });
    }) || props.product.variants[0];
});

const currentPrice = computed(() => currentVariant.value ? currentVariant.value.price : props.product.price);
const currentComparePrice = computed(() => currentVariant.value ? currentVariant.value.compare_at_price : props.product.compare_at_price);

const isCurrentInStock = computed(() => {
    if (currentVariant.value && currentVariant.value.is_in_stock !== undefined) {
        return currentVariant.value.is_in_stock;
    }
    return props.product.is_in_stock !== false;
});

const currentStockQty = computed(() => {
    if (currentVariant.value && currentVariant.value.stock_quantity !== undefined) {
        return currentVariant.value.stock_quantity;
    }
    return props.product.stock_quantity;
});

const formatPrice = (val) => {
    if (!val) return '';
    return '৳ ' + Number(val).toLocaleString('en-US');
};

const handleAddToCart = () => {
    if (props.product && isCurrentInStock.value) {
        addToCart(props.product, quantity.value, currentVariant.value);
    }
};

const incrementQuantity = () => {
    if (currentStockQty.value !== null && currentStockQty.value !== undefined && quantity.value >= currentStockQty.value) {
        return;
    }
    quantity.value++;
};

const decrementQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const reviewForm = useForm({
    rating: 5,
    reviewer_name: page.props.customer?.name || '',
    reviewer_email: page.props.customer?.email || '',
    title: '',
    body: '',
});

const reviewSuccess = ref(false);

const ratingLabels = computed(() => {
    const labels = tm('product.rating_labels') || [];
    return {
        0: labels[0] || t('product.your_rating'),
        1: labels[1] || '',
        2: labels[2] || '',
        3: labels[3] || '',
        4: labels[4] || '',
        5: labels[5] || '',
    };
});

const setRating = (star) => {
    // If user clicks the currently selected active star, deselect that specific star (reduce by 1)
    if (reviewForm.rating === star) {
        reviewForm.rating = star - 1;
    } else {
        reviewForm.rating = star;
    }
    hoverRating.value = 0;
};

const setHover = (star) => {
    hoverRating.value = star;
};

const clearHover = () => {
    hoverRating.value = 0;
};

const submitReview = () => {
    reviewForm.post(`/product/${props.product.slug}/reviews`, {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset('title', 'body');
            showReviewForm.value = false;
            reviewSuccess.value = true;
            setTimeout(() => {
                reviewSuccess.value = false;
            }, 6000);
        },
    });
};
</script>

<template>
    <Head :title="t('meta.product', { name: productTitle })">
        <meta name="description" :content="productShortDesc" />
        <meta property="og:title" :content="t('meta.product', { name: productTitle })" />
        <meta property="og:description" :content="productShortDesc" />
        <meta property="og:image" :content="activeImage" />
    </Head>

    <div :class="pageShellClass">
        <Header />

        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24 lg:pb-8 w-full">
            <OccasionRibbon v-if="occasion" class="detail-campaign" :kind="occasion.key" :data-occasion="occasion.key">
                <strong>{{ occasion.label }}</strong>
                <span>{{ occasion.lines.products }}</span>
                <OccasionCountdown v-if="occasion.ends_at" :ends-at="occasion.ends_at" />
            </OccasionRibbon>
            <!-- Flash Notification -->
            <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <span class="text-sm font-semibold">{{ page.props.flash.success }}</span>
            </div>

            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-sm text-[#64748B] mb-8 overflow-x-auto whitespace-nowrap">
                <Link href="/" class="hover:text-[#E86A28] transition-colors">{{ t('header.home') }}</Link>
                <ChevronRight class="w-4 h-4 shrink-0" />
                <Link :href="'/?category=' + product.category_slug" class="hover:text-[#E86A28] transition-colors">
                    {{ productCategory }}
                </Link>
                <ChevronRight class="w-4 h-4 shrink-0" />
                <span class="text-[#1E293B] font-medium truncate">{{ productTitle }}</span>
            </nav>

            <div
                class="flex flex-col md:flex-row gap-10 lg:gap-16 bg-white rounded-3xl p-6 lg:p-10 border shadow-sm"
                :class="occasion ? 'border-transparent product-sheet' : 'border-[#EAE3D6]'"
                :data-occasion="occasion?.key"
            >
                <!-- Gallery Column -->
                <div class="w-full md:w-1/2 flex flex-col gap-4 shrink-0">
                    <div
                        class="relative w-full bg-[#F7F3EC] rounded-2xl p-6 sm:p-8 flex items-center justify-center min-h-[350px] sm:min-h-[440px] border border-[#EAE3D6] overflow-hidden group touch-pan-y cursor-zoom-in"
                        role="button"
                        tabindex="0"
                        @click="zoomOpen = true"
                        @keydown.enter.prevent="zoomOpen = true"
                        @touchstart.passive="galleryTouchX = $event.changedTouches[0]?.clientX || 0"
                        @touchend.passive="onGallerySwipe($event)"
                    >
                        <img
                            :src="activeImage"
                            :alt="productTitle"
                            class="w-full h-auto max-h-[440px] mx-auto object-contain drop-shadow-md transition-transform duration-500 group-hover:scale-105 pointer-events-none"
                        />
                        <div v-if="!isCurrentInStock" class="absolute top-4 left-4 px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-lg shadow-sm z-10 pointer-events-none">
                            {{ t('product.out_of_stock') }}
                        </div>
                        <div v-else-if="currentComparePrice && currentComparePrice > currentPrice" class="absolute top-4 left-4 px-3 py-1 bg-[#E86A28] text-white text-xs font-bold rounded-lg shadow-sm z-10 pointer-events-none">
                            {{ t('home.offer_badge') }}
                        </div>
                        <button
                            type="button"
                            class="absolute bottom-4 right-4 z-10 h-9 px-3 bg-[#1E293B]/90 hover:bg-[#E86A28] text-white text-[11px] font-bold uppercase tracking-wider rounded-xl inline-flex items-center gap-1.5 transition-colors"
                            @click.stop="zoomOpen = true"
                        >
                            <ZoomIn class="w-3.5 h-3.5" />
                            Zoom
                        </button>
                        <template v-if="images.length > 1">
                            <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white/90 text-[#1E293B] hidden sm:inline-flex items-center justify-center shadow-sm" @click.stop="stepGallery(-1)">‹</button>
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white/90 text-[#1E293B] hidden sm:inline-flex items-center justify-center shadow-sm" @click.stop="stepGallery(1)">›</button>
                        </template>
                    </div>

                    <!-- Thumbnails -->
                    <div v-if="images.length > 1" class="flex items-center gap-3 overflow-x-auto pb-2">
                        <button
                            v-for="(img, idx) in images"
                            :key="idx"
                            type="button"
                            @click="activeImage = img"
                            class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border-2 bg-[#F7F3EC] p-1 flex-shrink-0 transition-all cursor-pointer"
                            :class="activeImage === img ? 'border-[#E86A28] ring-2 ring-[#E86A28]/20 shadow-xs' : 'border-[#EAE3D6] opacity-70 hover:opacity-100 hover:border-[#DCCFC0]'"
                        >
                            <img :src="img" :alt="productTitle + ' ' + (idx + 1)" class="w-full h-full object-contain" />
                        </button>
                    </div>
                </div>

                <!-- Info Column -->
                <div class="w-full md:w-1/2 flex flex-col">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-[#E86A28] uppercase tracking-wider">
                                {{ productCategory }}
                            </span>
                            <span v-if="product.brand_name" class="text-xs text-[#64748B] font-semibold bg-[#F1EBE1] px-2.5 py-0.5 rounded-full">
                                {{ product.brand_name }}
                            </span>
                        </div>
                        <span
                            class="text-xs font-bold px-3 py-1 rounded-full"
                            :class="isCurrentInStock ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ isCurrentInStock ? '● ' + t('product.in_stock_badge') : '✕ ' + t('product.out_of_stock') }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-[#1E293B] leading-tight mb-3">
                        {{ productTitle }}
                    </h1>

                    <!-- Rating Summary -->
                    <div class="flex items-center gap-2 mb-6">
                        <div class="flex items-center text-amber-400">
                            <Star
                                v-for="s in 5"
                                :key="s"
                                class="w-4 h-4"
                                :class="s <= Math.round(product.rating || 5) ? 'fill-amber-400 text-amber-400' : 'text-gray-300'"
                            />
                        </div>
                        <span class="text-sm font-bold text-[#1E293B]">{{ Number(product.rating || 5).toFixed(1) }}</span>
                        <a href="#reviews" class="text-xs text-[#64748B] hover:text-[#E86A28] underline underline-offset-2">
                            ({{ t('product.reviews_count', { count: product.reviews_count || 0 }) }})
                        </a>
                    </div>

                    <!-- Pricing -->
                    <div class="flex items-baseline gap-4 mb-6">
                        <span class="store-price text-3xl font-bold text-[#1E293B]">
                            {{ formatPrice(currentPrice) }}
                        </span>
                        <span
                            v-if="currentComparePrice && currentComparePrice > currentPrice"
                            class="text-xl text-[#94A3B8] line-through font-medium"
                        >
                            {{ formatPrice(currentComparePrice) }}
                        </span>
                        <span v-if="currentComparePrice && currentComparePrice > currentPrice" class="px-3 py-1 bg-[#FEE2E2] text-[#EF4444] text-xs font-bold rounded-lg">
                            {{ t('home.offer_badge') }}
                        </span>
                    </div>

                    <!-- Variant Selection -->
                    <div v-if="product.available_options && Object.keys(product.available_options).length > 0" class="mb-6 space-y-5">
                        <div v-for="(values, attrName) in product.available_options" :key="attrName">
                            <h3 class="text-sm font-semibold text-[#1E293B] uppercase tracking-wider mb-2.5">{{ attrName }}</h3>
                            <div class="flex flex-wrap gap-2.5">
                                <button
                                    v-for="value in values"
                                    :key="value"
                                    @click="selectedOptions[attrName] = value"
                                    type="button"
                                    class="px-4 py-2 rounded-xl text-sm font-medium border transition-all duration-200 cursor-pointer"
                                    :class="selectedOptions[attrName] === value ? 'border-[#E86A28] bg-[#E86A28] text-white shadow-md' : 'border-[#EAE3D6] bg-white text-[#475569] hover:border-[#DCCFC0] hover:bg-[#FAF7F2]'"
                                >
                                    {{ value }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Short Description -->
                    <div class="text-sm text-[#475569] leading-relaxed mb-8">
                        <p>{{ productShortDesc }}</p>
                    </div>

                    <div class="mt-auto border-t border-[#F0EAE1] pt-6">
                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mb-4">
                            <!-- Quantity -->
                            <div 
                                class="flex items-center border border-[#EAE3D6] rounded-xl bg-[#FAF7F2] h-14 w-full sm:w-auto"
                                :class="{ 'opacity-50 cursor-not-allowed': !isCurrentInStock }"
                            >
                                <button
                                    @click="decrementQuantity"
                                    :disabled="!isCurrentInStock || quantity <= 1"
                                    type="button"
                                    class="w-12 h-full flex items-center justify-center text-[#64748B] hover:text-[#1E293B] hover:bg-white rounded-l-xl transition-colors disabled:opacity-40"
                                >
                                    <Minus class="w-5 h-5" />
                                </button>
                                <span class="w-12 text-center font-bold text-lg text-[#1E293B]">
                                    {{ quantity }}
                                </span>
                                <button
                                    @click="incrementQuantity"
                                    :disabled="!isCurrentInStock || (currentStockQty !== null && quantity >= currentStockQty)"
                                    type="button"
                                    class="w-12 h-full flex items-center justify-center text-[#64748B] hover:text-[#1E293B] hover:bg-white rounded-r-xl transition-colors disabled:opacity-40"
                                >
                                    <Plus class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Add to Cart -->
                            <button
                                @click="handleAddToCart"
                                :disabled="!isCurrentInStock"
                                type="button"
                                class="flex-1 h-14 rounded-xl font-bold text-base flex items-center justify-center gap-2 transition-all shadow-md"
                                :class="isCurrentInStock 
                                    ? 'bg-[#E86A28] hover:bg-[#D35B1D] text-white cursor-pointer' 
                                    : 'bg-gray-200 text-gray-400 cursor-not-allowed shadow-none'"
                            >
                                <ShoppingBag class="w-5 h-5" />
                                <span>{{ isCurrentInStock ? t('product.add_to_cart') : t('product.out_of_stock') }}</span>
                            </button>
                        </div>

                        <!-- Wishlist -->
                        <button
                            @click="toggleWishlist(product)"
                            type="button"
                            class="w-full h-14 border border-[#EAE3D6] bg-white hover:bg-[#FAF7F2] text-[#475569] hover:text-[#1E293B] rounded-xl font-semibold text-base flex items-center justify-center gap-2 transition-all cursor-pointer"
                        >
                            <Heart 
                                class="w-5 h-5 transition-colors" 
                                :class="{ 'fill-[#E86A28] text-[#E86A28]': isInWishlist(product.id) }" 
                            />
                            <span>{{ isInWishlist(product.id) ? t('product.wishlist_added') : t('product.wishlist_add') }}</span>
                        </button>

                        <!-- Assurance Perks -->
                        <div class="grid grid-cols-3 gap-2 pt-6 mt-6 border-t border-[#F0EAE1] text-center">
                            <div class="flex flex-col items-center gap-1 p-2 rounded-xl bg-[#FAF7F2]">
                                <Truck class="w-4 h-4 text-[#E86A28]" />
                                <span class="text-[11px] font-semibold text-[#1E293B]">{{ t('product.perk_fast_delivery') }}</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 p-2 rounded-xl bg-[#FAF7F2]">
                                <ShieldCheck class="w-4 h-4 text-[#E86A28]" />
                                <span class="text-[11px] font-semibold text-[#1E293B]">{{ t('product.perk_cod') }}</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 p-2 rounded-xl bg-[#FAF7F2]">
                                <RotateCcw class="w-4 h-4 text-[#E86A28]" />
                                <span class="text-[11px] font-semibold text-[#1E293B]">{{ t('product.perk_easy_return') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Description Section -->
            <div class="mt-10 bg-white rounded-3xl p-6 sm:p-10 border border-[#EAE3D6] shadow-sm">
                <h2 class="text-xl font-bold text-[#1E293B] mb-4 pb-3 border-b border-[#F0EAE1]">{{ t('product.details_heading') }}</h2>
                <div class="prose max-w-none text-[#475569] leading-relaxed text-sm sm:text-base">
                    <div v-if="productDescriptionHtml" v-html="productDescriptionHtml"></div>
                    <p v-else>{{ productShortDesc || t('product.fallback_description') }}</p>
                </div>
            </div>

            <!-- Customer Reviews Section -->
            <div id="reviews" class="mt-8 bg-white rounded-3xl p-6 sm:p-10 border border-[#EAE3D6] shadow-sm">
                <!-- Section Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-[#F0EAE1]">
                    <div>
                        <h2 class="text-xl font-bold text-[#1E293B] flex items-center gap-2">
                            <MessageSquare class="w-5 h-5 text-[#E86A28]" />
                            <span>{{ t('product.reviews_heading') }}</span>
                        </h2>
                        <p class="text-xs text-[#64748B] mt-1">{{ t('product.reviews_subheading') }}</p>
                    </div>

                    <button
                        @click="showReviewForm = !showReviewForm"
                        type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#E86A28] hover:bg-[#D35B1D] text-white rounded-xl text-sm font-bold transition-all shadow-sm hover:shadow cursor-pointer w-fit"
                    >
                        <MessageSquare class="w-4 h-4" />
                        <span>{{ showReviewForm ? t('product.close_review_form') : t('product.write_review_cta') }}</span>
                    </button>
                </div>

                <!-- Review Breakdown & Overview (Industry Standard Style) -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 py-8 border-b border-[#F0EAE1]">
                    <!-- Left: Overall Rating Box -->
                    <div class="md:col-span-4 flex flex-col items-center justify-center p-6 bg-[#FAF7F2] rounded-2xl border border-[#EAE3D6] text-center">
                        <span class="text-5xl font-black text-[#1E293B] tracking-tight">
                            {{ Number(product.rating || 5).toFixed(1) }}
                        </span>
                        <div class="flex items-center gap-1 text-amber-400 my-2">
                            <Star
                                v-for="s in 5"
                                :key="s"
                                class="w-5 h-5"
                                :class="s <= Math.round(product.rating || 5) ? 'fill-amber-400 text-amber-400' : 'text-gray-300'"
                            />
                        </div>
                        <span class="text-sm font-semibold text-[#1E293B]">{{ t('product.avg_rating') }}</span>
                        <span class="text-xs text-[#64748B] mt-0.5">{{ t('product.verified_reviews', { count: product.reviews_count || (product.reviews ? product.reviews.length : 0) }) }}</span>
                    </div>

                    <!-- Right: Star Rating Bars -->
                    <div class="md:col-span-8 flex flex-col justify-center gap-2.5">
                        <div 
                            v-for="s in [5, 4, 3, 2, 1]" 
                            :key="s"
                            class="flex items-center gap-3 text-xs sm:text-sm"
                        >
                            <span class="w-12 font-bold text-[#475569] flex items-center gap-1 shrink-0">
                                <span>{{ s }}</span>
                                <Star class="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
                            </span>
                            <div class="flex-1 h-3 bg-[#EAE3D6]/60 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-amber-400 rounded-full transition-all duration-500"
                                    :style="{ width: (product.rating_breakdown && product.rating_breakdown[s] ? product.rating_breakdown[s].percentage : (s === 5 ? 100 : 0)) + '%' }"
                                ></div>
                            </div>
                            <span class="w-12 text-right text-xs font-semibold text-[#64748B] shrink-0">
                                {{ t('product.review_count_unit', { count: product.rating_breakdown && product.rating_breakdown[s] ? product.rating_breakdown[s].count : 0 }) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Review Success Alert -->
                <div v-if="reviewSuccess || page.props.flash?.success" class="mt-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3 animate-fade-in">
                    <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                    <span class="text-sm font-semibold">{{ page.props.flash?.success || t('product.review_thanks') }}</span>
                </div>

                <!-- Modern Interactive Review Form Box -->
                <div v-if="showReviewForm" class="mt-6 p-6 sm:p-8 rounded-2xl bg-[#FDFBF7] border-2 border-[#E86A28]/30 shadow-md">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-[#EAE3D6]">
                        <div>
                            <h3 class="text-lg font-bold text-[#1E293B]">{{ t('product.review_form_title') }}</h3>
                            <p class="text-xs text-[#64748B]">{{ t('product.review_form_subtitle') }}</p>
                        </div>
                        <button 
                            type="button" 
                            @click="showReviewForm = false"
                            class="p-2 text-[#64748B] hover:text-[#1E293B] hover:bg-[#FAF7F2] rounded-xl transition-colors cursor-pointer"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    
                    <form @submit.prevent="submitReview" class="space-y-6">
                        <!-- Modern Interactive Star Rating Input with Dynamic Feedback -->
                        <div class="p-4 bg-white rounded-2xl border border-[#EAE3D6]">
                            <label class="block text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">
                                {{ t('product.review_rating_required') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <div class="flex items-center gap-1" @mouseleave="clearHover">
                                    <button
                                        v-for="star in 5"
                                        :key="star"
                                        type="button"
                                        @click="setRating(star)"
                                        @mouseenter="setHover(star)"
                                        class="p-1.5 hover:scale-125 active:scale-90 transition-transform duration-100 focus:outline-none cursor-pointer"
                                        :title="t('product.star_title', { count: star })"
                                    >
                                        <Star
                                            class="w-8 h-8 pointer-events-none transition-colors"
                                            :class="star <= (hoverRating || reviewForm.rating) ? 'fill-amber-400 text-amber-400 drop-shadow-sm' : 'fill-transparent text-gray-300'"
                                        />
                                    </button>
                                </div>
                                <div class="px-3.5 py-1.5 bg-[#FAF7F2] border border-[#EAE3D6] rounded-xl text-xs sm:text-sm font-bold text-[#E86A28] w-fit">
                                    {{ ratingLabels[hoverRating || reviewForm.rating] }}
                                </div>
                            </div>
                            <p v-if="reviewForm.errors.rating" class="text-xs text-red-500 mt-2">{{ reviewForm.errors.rating }}</p>
                        </div>

                        <!-- Name & Email Inputs -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#475569] mb-1.5">
                                    {{ t('product.reviewer_name') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="reviewForm.reviewer_name"
                                    type="text"
                                    required
                                    :placeholder="t('product.review_name_placeholder')"
                                    class="w-full px-4 py-3 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-2 focus:ring-[#E86A28]/20 outline-hidden bg-white font-medium"
                                />
                                <p v-if="reviewForm.errors.reviewer_name" class="text-xs text-red-500 mt-1">{{ reviewForm.errors.reviewer_name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#475569] mb-1.5">
                                    {{ t('product.reviewer_email') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="reviewForm.reviewer_email"
                                    type="email"
                                    required
                                    :placeholder="t('product.review_email_placeholder')"
                                    class="w-full px-4 py-3 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-2 focus:ring-[#E86A28]/20 outline-hidden bg-white font-medium"
                                />
                                <p v-if="reviewForm.errors.reviewer_email" class="text-xs text-red-500 mt-1">{{ reviewForm.errors.reviewer_email }}</p>
                            </div>
                        </div>

                        <!-- Review Title -->
                        <div>
                            <label class="block text-xs font-bold text-[#475569] mb-1.5">
                                {{ t('product.review_title') }} <span class="text-[#94A3B8] font-normal">({{ t('product.optional') }})</span>
                            </label>
                            <input
                                v-model="reviewForm.title"
                                type="text"
                                :placeholder="t('product.review_title_placeholder')"
                                class="w-full px-4 py-3 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-2 focus:ring-[#E86A28]/20 outline-hidden bg-white font-medium"
                            />
                            <p v-if="reviewForm.errors.title" class="text-xs text-red-500 mt-1">{{ reviewForm.errors.title }}</p>
                        </div>

                        <!-- Review Body -->
                        <div>
                            <label class="block text-xs font-bold text-[#475569] mb-1.5">
                                {{ t('product.review_body') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="reviewForm.body"
                                rows="4"
                                required
                                :placeholder="t('product.review_body_placeholder')"
                                class="w-full px-4 py-3 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-2 focus:ring-[#E86A28]/20 outline-hidden bg-white font-medium leading-relaxed"
                            ></textarea>
                            <p v-if="reviewForm.errors.body" class="text-xs text-red-500 mt-1">{{ reviewForm.errors.body }}</p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="showReviewForm = false"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-[#64748B] hover:bg-[#FAF7F2] transition-colors cursor-pointer"
                            >
                                {{ t('product.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="reviewForm.processing"
                                class="px-8 py-3 rounded-xl text-sm font-bold bg-[#E86A28] hover:bg-[#D35B1D] text-white shadow-md hover:shadow-lg transition-all disabled:opacity-50 cursor-pointer"
                            >
                                {{ reviewForm.processing ? t('product.submitting_review') : t('product.submit_review') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Review Listing Section -->
                <div class="mt-8 divide-y divide-[#F0EAE1]">
                    <div
                        v-for="review in product.reviews"
                        :key="review.id"
                        class="py-6 first:pt-2 last:pb-2"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#E86A28] to-[#F59E0B] text-white font-bold flex items-center justify-center text-sm shadow-xs uppercase">
                                    {{ review.reviewer_name ? review.reviewer_name.charAt(0) : t('reviews.satisfied_customer').charAt(0) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-[#1E293B]">{{ review.reviewer_name }}</h4>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            <Check class="w-3 h-3 stroke-[2.5]" />
                                            {{ t('product.verified_purchase') }}
                                        </span>
                                    </div>
                                    <span class="text-xs text-[#94A3B8]">{{ review.created_at }}</span>
                                </div>
                            </div>

                            <!-- Star Rating -->
                            <div class="flex items-center text-amber-400">
                                <Star
                                    v-for="s in 5"
                                    :key="s"
                                    class="w-4 h-4"
                                    :class="s <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-gray-300'"
                                />
                            </div>
                        </div>

                        <h5 v-if="review.title" class="text-sm sm:text-base font-bold text-[#1E293B] mb-1.5">
                            {{ review.title }}
                        </h5>
                        <p class="text-sm text-[#475569] leading-relaxed">
                            {{ review.body }}
                        </p>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!product.reviews || product.reviews.length === 0" class="py-14 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-[#FAF7F2] border border-[#EAE3D6] flex items-center justify-center text-[#E86A28]">
                            <MessageSquare class="w-8 h-8" />
                        </div>
                        <h4 class="text-base font-bold text-[#1E293B]">{{ t('product.no_reviews_title') }}</h4>
                        <p class="text-sm text-[#64748B] max-w-md mx-auto mt-1 mb-5">{{ t('product.no_reviews_body') }}</p>
                        <button
                            v-if="!showReviewForm"
                            @click="showReviewForm = true"
                            type="button"
                            class="px-5 py-2.5 bg-[#E86A28] hover:bg-[#D35B1D] text-white rounded-xl text-sm font-bold transition-all shadow-sm cursor-pointer"
                        >
                            {{ t('product.first_review_cta') }}
                        </button>
                    </div>
                </div>
                <div v-if="relatedProducts.length" class="mt-10 pt-8 border-t border-[#EAE3D6]">
                    <h2 class="text-xl sm:text-2xl font-bold text-[#1E293B] mb-5">{{ t('product.related') }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                        <ProductCard v-for="item in relatedProducts" :key="item.id" :product="item" />
                    </div>
                </div>

                <div v-if="recentProducts.length" class="mt-10 pt-8 border-t border-[#EAE3D6]">
                    <h2 class="text-xl sm:text-2xl font-bold text-[#1E293B] mb-5">{{ t('product.recently_viewed') }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                        <ProductCard v-for="item in recentProducts" :key="'recent-' + item.id" :product="item" />
                    </div>
                </div>
            </div>
        </main>

        <Teleport to="body">
            <div
                v-if="zoomOpen && activeImage"
                class="fixed inset-0 z-[60] bg-[#1E293B]/80 flex items-center justify-center p-4"
                @click="zoomOpen = false"
            >
                <button type="button" class="absolute top-4 right-4 w-10 h-10 bg-white text-[#1E293B] rounded-xl inline-flex items-center justify-center" @click="zoomOpen = false">
                    <X class="w-5 h-5" />
                </button>
                <img :src="activeImage" :alt="productTitle" class="max-h-[90vh] max-w-full object-contain" @click.stop />
            </div>
        </Teleport>

        <div class="lg:hidden fixed bottom-0 inset-x-0 z-30 border-t border-[#EAE3D6] bg-white/95 backdrop-blur-sm p-3 shadow-[0_-8px_24px_rgba(0,0,0,0.06)]">
            <div class="flex items-center gap-2 max-w-7xl mx-auto">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-[#E86A28] truncate">{{ formatPrice(currentPrice) }}</p>
                </div>
                <button
                    type="button"
                    class="ml-auto h-11 px-5 rounded-xl text-sm font-bold inline-flex items-center justify-center gap-2 shrink-0 transition-colors"
                    :class="isCurrentInStock ? 'bg-[#E86A28] hover:bg-[#D35B1D] text-white' : 'bg-[#EAE3D6] text-[#94A3B8] cursor-not-allowed'"
                    :disabled="!isCurrentInStock"
                    @click="handleAddToCart"
                >
                    <ShoppingBag class="w-4 h-4" />
                    {{ isCurrentInStock ? t('product.add_to_cart') : t('product.out_of_stock') }}
                </button>
            </div>
        </div>

        <Footer />
    </div>
</template>

<style scoped>
.detail-campaign {
    margin-bottom: 1.25rem;
}

.detail-campaign :deep(.countdown-cell) {
    background: rgb(255 255 255 / 16%);
    color: var(--ink);
}

.product-sheet {
    border-color: color-mix(in srgb, var(--campaign) 42%, white);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--campaign) 16%, transparent);
}
</style>
