<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { 
    ShoppingBag, Plus, Minus, Heart, ChevronRight, Star, 
    CheckCircle2, MessageSquare, ShieldCheck, Truck, RotateCcw 
} from 'lucide-vue-next';
import Header from '../Components/Header.vue';
import Footer from '../Components/Footer.vue';
import CartDrawer from '../Components/CartDrawer.vue';
import WishlistDrawer from '../Components/WishlistDrawer.vue';
import { useCart } from '../Composables/useCart';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    }
});

const page = usePage();
const { addToCart, toggleWishlist, isInWishlist } = useCart();
const quantity = ref(1);
const activeImage = ref(props.product.image);
const showReviewForm = ref(false);

const images = computed(() => {
    if (props.product.images && props.product.images.length > 0) {
        return props.product.images;
    }
    return [props.product.image];
});

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
    title: '',
    body: '',
});

const submitReview = () => {
    reviewForm.post(`/product/${props.product.slug}/reviews`, {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset();
            showReviewForm.value = false;
        },
    });
};
</script>

<template>
    <Head :title="product.name + ' - Nobo Shop'">
        <meta name="description" :content="product.short_description || product.name" />
        <meta property="og:title" :content="product.name + ' - Nobo Shop'" />
        <meta property="og:description" :content="product.short_description || product.name" />
        <meta property="og:image" :content="activeImage" />
    </Head>

    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col">
        <Header />

        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            <!-- Flash Notification -->
            <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <span class="text-sm font-semibold">{{ page.props.flash.success }}</span>
            </div>

            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-sm text-[#64748B] mb-8 overflow-x-auto whitespace-nowrap">
                <Link href="/" class="hover:text-[#E86A28] transition-colors">হোম</Link>
                <ChevronRight class="w-4 h-4 shrink-0" />
                <Link :href="'/?category=' + product.category_slug" class="hover:text-[#E86A28] transition-colors">
                    {{ product.category_name }}
                </Link>
                <ChevronRight class="w-4 h-4 shrink-0" />
                <span class="text-[#1E293B] font-medium truncate">{{ product.name }}</span>
            </nav>

            <div class="flex flex-col md:flex-row gap-10 lg:gap-16 bg-white rounded-3xl p-6 lg:p-10 border border-[#EAE3D6] shadow-sm">
                <!-- Gallery Column -->
                <div class="w-full md:w-1/2 flex flex-col gap-4 shrink-0">
                    <div class="relative w-full bg-[#F7F3EC] rounded-2xl p-6 sm:p-8 flex items-center justify-center min-h-[350px] sm:min-h-[440px] border border-[#EAE3D6] overflow-hidden">
                        <img
                            :src="activeImage"
                            :alt="product.name"
                            class="w-full h-auto max-h-[440px] object-contain drop-shadow-md transition-transform duration-500 hover:scale-105"
                        />
                        <div v-if="!isCurrentInStock" class="absolute top-4 left-4 px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-lg shadow-sm">
                            স্টক শেষ
                        </div>
                        <div v-else-if="currentComparePrice && currentComparePrice > currentPrice" class="absolute top-4 left-4 px-3 py-1 bg-[#E86A28] text-white text-xs font-bold rounded-lg shadow-sm">
                            অফার
                        </div>
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
                            <img :src="img" :alt="product.name + ' ' + (idx + 1)" class="w-full h-full object-contain" />
                        </button>
                    </div>
                </div>

                <!-- Info Column -->
                <div class="w-full md:w-1/2 flex flex-col">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-[#E86A28] uppercase tracking-wider">
                                {{ product.category_name }}
                            </span>
                            <span v-if="product.brand_name" class="text-xs text-[#64748B] font-semibold bg-[#F1EBE1] px-2.5 py-0.5 rounded-full">
                                {{ product.brand_name }}
                            </span>
                        </div>
                        <span
                            class="text-xs font-bold px-3 py-1 rounded-full"
                            :class="isCurrentInStock ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ isCurrentInStock ? '● ইন স্টক' : '✕ স্টক শেষ' }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-[#1E293B] leading-tight mb-3">
                        {{ product.name }}
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
                            ({{ product.reviews_count || 0 }} টি রিভিউ)
                        </a>
                    </div>

                    <!-- Pricing -->
                    <div class="flex items-baseline gap-4 mb-6">
                        <span class="text-3xl font-bold text-[#1E293B]">
                            {{ formatPrice(currentPrice) }}
                        </span>
                        <span
                            v-if="currentComparePrice && currentComparePrice > currentPrice"
                            class="text-xl text-[#94A3B8] line-through font-medium"
                        >
                            {{ formatPrice(currentComparePrice) }}
                        </span>
                        <span v-if="currentComparePrice && currentComparePrice > currentPrice" class="px-3 py-1 bg-[#FEE2E2] text-[#EF4444] text-xs font-bold rounded-lg">
                            অফার
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
                        <p>{{ product.short_description || 'এই পণ্যটির চমৎকার ডিজাইন এবং উন্নত কোয়ালিটি আপনাকে মুগ্ধ করবে।' }}</p>
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
                                <span>{{ isCurrentInStock ? 'কার্টে যোগ করুন' : 'স্টক শেষ' }}</span>
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
                            <span>{{ isInWishlist(product.id) ? 'উইশলিস্টে যুক্ত আছে' : 'উইশলিস্টে যুক্ত করুন' }}</span>
                        </button>

                        <!-- Assurance Perks -->
                        <div class="grid grid-cols-3 gap-2 pt-6 mt-6 border-t border-[#F0EAE1] text-center">
                            <div class="flex flex-col items-center gap-1 p-2 rounded-xl bg-[#FAF7F2]">
                                <Truck class="w-4 h-4 text-[#E86A28]" />
                                <span class="text-[11px] font-semibold text-[#1E293B]">দ্রুত ডেলিভারি</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 p-2 rounded-xl bg-[#FAF7F2]">
                                <ShieldCheck class="w-4 h-4 text-[#E86A28]" />
                                <span class="text-[11px] font-semibold text-[#1E293B]">ক্যাশ অন ডেলিভারি</span>
                            </div>
                            <div class="flex flex-col items-center gap-1 p-2 rounded-xl bg-[#FAF7F2]">
                                <RotateCcw class="w-4 h-4 text-[#E86A28]" />
                                <span class="text-[11px] font-semibold text-[#1E293B]">সহজ রিটার্ন</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Description Section -->
            <div class="mt-10 bg-white rounded-3xl p-6 sm:p-10 border border-[#EAE3D6] shadow-sm">
                <h2 class="text-xl font-bold text-[#1E293B] mb-4 pb-3 border-b border-[#F0EAE1]">পণ্যের বিস্তারিত বিবরণ</h2>
                <div class="prose max-w-none text-[#475569] leading-relaxed text-sm sm:text-base">
                    <div v-if="product.description" v-html="product.description"></div>
                    <p v-else>{{ product.short_description || 'এই পণ্যটির চমৎকার ডিজাইন এবং উন্নত কোয়ালিটি আপনাকে মুগ্ধ করবে। প্রতিদিনের ব্যবহারের জন্য একটি পারফেক্ট পছন্দ।' }}</p>
                </div>
            </div>

            <!-- Customer Reviews Section -->
            <div id="reviews" class="mt-8 bg-white rounded-3xl p-6 sm:p-10 border border-[#EAE3D6] shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-[#F0EAE1]">
                    <div>
                        <h2 class="text-xl font-bold text-[#1E293B] flex items-center gap-2">
                            <MessageSquare class="w-5 h-5 text-[#E86A28]" />
                            <span>গ্রাহকদের রিভিউ ও রেটিং</span>
                        </h2>
                        <div class="flex items-center gap-3 mt-2">
                            <div class="flex items-center text-amber-400">
                                <Star
                                    v-for="s in 5"
                                    :key="s"
                                    class="w-4 h-4"
                                    :class="s <= Math.round(product.rating || 5) ? 'fill-amber-400 text-amber-400' : 'text-gray-300'"
                                />
                            </div>
                            <span class="text-sm font-bold text-[#1E293B]">{{ Number(product.rating || 5).toFixed(1) }} / ৫</span>
                            <span class="text-xs text-[#64748B]">({{ product.reviews_count || 0 }} টি রিভিউ)</span>
                        </div>
                    </div>

                    <button
                        @click="showReviewForm = !showReviewForm"
                        type="button"
                        class="px-5 py-2.5 bg-[#FAF7F2] hover:bg-[#E86A28] text-[#1E293B] hover:text-white border border-[#EAE3D6] hover:border-[#E86A28] rounded-xl text-sm font-semibold transition-all shadow-xs w-fit cursor-pointer"
                    >
                        {{ showReviewForm ? 'ফর্ম লুকান' : 'একটি রিভিউ দিন' }}
                    </button>
                </div>

                <!-- Review Form Drawer / Box -->
                <div v-if="showReviewForm" class="mt-6 p-6 rounded-2xl bg-[#FDFBF7] border border-[#EAE3D6]">
                    <h3 class="text-base font-bold text-[#1E293B] mb-4">আপনার রিভিউ লিখুন</h3>
                    
                    <form @submit.prevent="submitReview" class="space-y-4">
                        <!-- Star Rating Input -->
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] mb-1.5 uppercase">আপনার রেটিং</label>
                            <div class="flex items-center gap-2">
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    type="button"
                                    @click="reviewForm.rating = star"
                                    class="p-1 hover:scale-110 transition-transform focus:outline-hidden cursor-pointer"
                                >
                                    <Star
                                        class="w-6 h-6 transition-colors"
                                        :class="star <= reviewForm.rating ? 'fill-amber-400 text-amber-400' : 'text-gray-300 hover:text-amber-300'"
                                    />
                                </button>
                                <span class="text-sm font-bold text-[#1E293B] ml-2">{{ reviewForm.rating }} স্টার</span>
                            </div>
                            <p v-if="reviewForm.errors.rating" class="text-xs text-red-500 mt-1">{{ reviewForm.errors.rating }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[#64748B] mb-1.5">আপনার নাম</label>
                                <input
                                    v-model="reviewForm.reviewer_name"
                                    type="text"
                                    placeholder="যেমন: মোঃ করিম"
                                    class="w-full px-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-white"
                                />
                                <p v-if="reviewForm.errors.reviewer_name" class="text-xs text-red-500 mt-1">{{ reviewForm.errors.reviewer_name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#64748B] mb-1.5">রিভিউ শিরোনাম (ঐচ্ছিক)</label>
                                <input
                                    v-model="reviewForm.title"
                                    type="text"
                                    placeholder="যেমন: অসাধারণ কোয়ালিটি ও দ্রুত ডেলিভারি"
                                    class="w-full px-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-white"
                                />
                                <p v-if="reviewForm.errors.title" class="text-xs text-red-500 mt-1">{{ reviewForm.errors.title }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] mb-1.5">আপনার মতামত ও অভিজ্ঞতা *</label>
                            <textarea
                                v-model="reviewForm.body"
                                rows="3"
                                required
                                placeholder="পণ্যটি সম্পর্কে আপনার বিস্তারিত অভিজ্ঞতা শেয়ার করুন..."
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-white"
                            ></textarea>
                            <p v-if="reviewForm.errors.body" class="text-xs text-red-500 mt-1">{{ reviewForm.errors.body }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="showReviewForm = false"
                                class="px-4 py-2 rounded-xl text-sm font-semibold text-[#64748B] hover:bg-gray-100 transition-colors cursor-pointer"
                            >
                                বাতিল
                            </button>
                            <button
                                type="submit"
                                :disabled="reviewForm.processing"
                                class="px-6 py-2.5 rounded-xl text-sm font-bold bg-[#E86A28] hover:bg-[#D35B1D] text-white shadow-sm transition-all disabled:opacity-50 cursor-pointer"
                            >
                                {{ reviewForm.processing ? 'জমা দেওয়া হচ্ছে...' : 'রিভিউ সাবমিট করুন' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Review Listing -->
                <div class="mt-6 divide-y divide-[#F0EAE1]">
                    <div
                        v-for="review in product.reviews"
                        :key="review.id"
                        class="py-5 first:pt-0 last:pb-0"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#F3ECE2] text-[#E86A28] font-bold flex items-center justify-center text-sm">
                                    {{ review.reviewer_name ? review.reviewer_name.charAt(0) : 'ক' }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-[#1E293B]">{{ review.reviewer_name }}</h4>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <div class="flex items-center text-amber-400">
                                            <Star
                                                v-for="s in 5"
                                                :key="s"
                                                class="w-3.5 h-3.5"
                                                :class="s <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-gray-300'"
                                            />
                                        </div>
                                        <span class="text-xs text-[#94A3B8]">{{ review.created_at }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h5 v-if="review.title" class="text-sm font-semibold text-[#1E293B] mt-2 mb-1">
                            {{ review.title }}
                        </h5>
                        <p class="text-sm text-[#475569] leading-relaxed">
                            {{ review.body }}
                        </p>
                    </div>

                    <div v-if="!product.reviews || product.reviews.length === 0" class="py-12 text-center">
                        <MessageSquare class="w-10 h-10 text-[#CBD5E1] mx-auto mb-3" />
                        <p class="text-base font-semibold text-[#1E293B]">এখনো কোনো রিভিউ দেওয়া হয়নি</p>
                        <p class="text-sm text-[#64748B] mt-1">পণ্যটি ব্যবহার করে আপনার মূল্যবান মতামত সবার সাথে শেয়ার করুন।</p>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
        <WishlistDrawer />
        <CartDrawer />
    </div>
</template>
