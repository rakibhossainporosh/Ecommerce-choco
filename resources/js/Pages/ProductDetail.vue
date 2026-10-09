<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBag, Plus, Minus, Heart, ChevronRight } from 'lucide-vue-next';
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

const { addToCart, toggleWishlist, isInWishlist } = useCart();
const quantity = ref(1);

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

const formatPrice = (val) => {
    if (!val) return '';
    return '৳ ' + Number(val).toLocaleString('en-US');
};

const handleAddToCart = () => {
    if (props.product) {
        addToCart(props.product, quantity.value);
    }
};
</script>

<template>
    <Head :title="product.name + ' - Nobo Shop'" />

    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col">
        <Header />

        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-sm text-[#64748B] mb-8">
                <Link href="/" class="hover:text-[#E86A28] transition-colors">হোম</Link>
                <ChevronRight class="w-4 h-4" />
                <Link :href="'/?category=' + product.category_slug" class="hover:text-[#E86A28] transition-colors">
                    {{ product.category_name }}
                </Link>
                <ChevronRight class="w-4 h-4" />
                <span class="text-[#1E293B] font-medium">{{ product.name }}</span>
            </nav>

            <div class="flex flex-col md:flex-row gap-10 lg:gap-16 bg-white rounded-3xl p-6 lg:p-10 border border-[#EAE3D6] shadow-sm">
                <!-- Image -->
                <div class="w-full md:w-1/2 bg-[#F7F3EC] rounded-2xl p-8 flex items-center justify-center shrink-0">
                    <img
                        :src="product.image"
                        :alt="product.name"
                        class="w-full h-auto max-h-[500px] object-contain drop-shadow-lg transition-transform duration-500 hover:scale-105"
                    />
                </div>

                <!-- Info -->
                <div class="w-full md:w-1/2 flex flex-col">
                    <span class="text-sm font-medium text-[#E86A28] uppercase tracking-wider block mb-3">
                        {{ product.category_name }}
                    </span>
                    <h1 class="text-3xl md:text-4xl font-bold text-[#1E293B] leading-tight mb-4">
                        {{ product.name }}
                    </h1>

                    <div class="flex items-center gap-4 mb-8">
                        <span class="text-3xl font-bold text-[#1E293B]">
                            {{ formatPrice(currentPrice) }}
                        </span>
                        <span
                            v-if="currentComparePrice && currentComparePrice > currentPrice"
                            class="text-xl text-[#94A3B8] line-through font-medium"
                        >
                            {{ formatPrice(currentComparePrice) }}
                        </span>
                        <span v-if="currentComparePrice && currentComparePrice > currentPrice" class="px-3 py-1 bg-[#FEE2E2] text-[#EF4444] text-sm font-bold rounded-lg ml-2">
                            অফার
                        </span>
                    </div>

                    <!-- Variant Selection -->
                    <div v-if="product.available_options && Object.keys(product.available_options).length > 0" class="mb-8 space-y-6">
                        <div v-for="(values, attrName) in product.available_options" :key="attrName">
                            <h3 class="text-sm font-semibold text-[#1E293B] uppercase tracking-wider mb-3">{{ attrName }}</h3>
                            <div class="flex flex-wrap gap-3">
                                <button
                                    v-for="value in values"
                                    :key="value"
                                    @click="selectedOptions[attrName] = value"
                                    type="button"
                                    class="px-4 py-2 rounded-xl text-sm font-medium border transition-all duration-200"
                                    :class="selectedOptions[attrName] === value ? 'border-[#E86A28] bg-[#E86A28] text-white shadow-md' : 'border-[#EAE3D6] bg-white text-[#475569] hover:border-[#DCCFC0] hover:bg-[#FAF7F2]'"
                                >
                                    {{ value }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="prose prose-base text-[#475569] leading-relaxed mb-10">
                        <p v-if="product.description" v-html="product.description"></p>
                        <p v-else>{{ product.short_description || 'এই পণ্যটির চমৎকার ডিজাইন এবং উন্নত কোয়ালিটি আপনাকে মুগ্ধ করবে। প্রতিদিনের ব্যবহারের জন্য একটি পারফেক্ট পছন্দ।' }}</p>
                    </div>

                    <div class="mt-auto border-t border-[#F0EAE1] pt-8">
                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mb-4">
                            <!-- Quantity -->
                            <div class="flex items-center border border-[#EAE3D6] rounded-xl bg-[#FAF7F2] h-14 w-full sm:w-auto">
                                <button
                                    @click="quantity > 1 ? quantity-- : null"
                                    type="button"
                                    class="w-12 h-full flex items-center justify-center text-[#64748B] hover:text-[#1E293B] hover:bg-white rounded-l-xl transition-colors"
                                >
                                    <Minus class="w-5 h-5" />
                                </button>
                                <span class="w-12 text-center font-bold text-lg text-[#1E293B]">
                                    {{ quantity }}
                                </span>
                                <button
                                    @click="quantity++"
                                    type="button"
                                    class="w-12 h-full flex items-center justify-center text-[#64748B] hover:text-[#1E293B] hover:bg-white rounded-r-xl transition-colors"
                                >
                                    <Plus class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Add to Cart -->
                            <button
                                @click="handleAddToCart"
                                type="button"
                                class="flex-1 h-14 bg-[#E86A28] hover:bg-[#D35B1D] text-white rounded-xl font-bold text-base flex items-center justify-center gap-2 transition-all shadow-md"
                            >
                                <ShoppingBag class="w-5 h-5" />
                                <span>কার্টে যোগ করুন</span>
                            </button>
                        </div>

                        <!-- Wishlist -->
                        <button
                            @click="toggleWishlist(product)"
                            type="button"
                            class="w-full h-14 border border-[#EAE3D6] bg-white hover:bg-[#FAF7F2] text-[#475569] hover:text-[#1E293B] rounded-xl font-semibold text-base flex items-center justify-center gap-2 transition-all"
                        >
                            <Heart 
                                class="w-5 h-5 transition-colors" 
                                :class="{ 'fill-[#E86A28] text-[#E86A28]': isInWishlist(product.id) }" 
                            />
                            <span>{{ isInWishlist(product.id) ? 'উইশলিস্টে যুক্ত আছে' : 'উইশলিস্টে যুক্ত করুন' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
        <WishlistDrawer />
        <CartDrawer />
    </div>
</template>
