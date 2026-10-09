<script setup>
import { ref, watch } from 'vue';
import { X, ShoppingBag, Plus, Minus, Heart } from 'lucide-vue-next';
import { useCart } from '../Composables/useCart';

const props = defineProps({
    product: {
        type: Object,
        default: null
    },
    isOpen: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const { addToCart, toggleWishlist, isInWishlist } = useCart();
const quantity = ref(1);

// Reset quantity when modal opens for a new product
watch(() => props.product, () => {
    quantity.value = 1;
});

const formatPrice = (val) => {
    if (!val) return '';
    return '৳ ' + Number(val).toLocaleString('en-US');
};

const handleAddToCart = () => {
    if (props.product) {
        addToCart(props.product, quantity.value);
        emit('close');
    }
};
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isOpen && product" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" @click.self="$emit('close')">
            <!-- Overlay Backdrop -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-xs pointer-events-none"></div>

            <!-- Modal Content -->
            <div class="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row max-h-[90vh]">
                <!-- Close Button -->
                <button
                    @click="$emit('close')"
                    type="button"
                    class="absolute top-4 right-4 z-10 p-2 text-[#94A3B8] hover:text-[#1E293B] bg-white/80 backdrop-blur-sm rounded-full transition-colors shadow-sm"
                >
                    <X class="w-5 h-5" />
                </button>

                <!-- Product Image -->
                <div class="w-full md:w-1/2 bg-[#F7F3EC] flex items-center justify-center p-8 shrink-0">
                    <img
                        :src="product.image"
                        :alt="product.name"
                        class="w-full h-auto max-h-[400px] object-contain rounded-2xl drop-shadow-md"
                    />
                </div>

                <!-- Product Details -->
                <div class="w-full md:w-1/2 p-6 md:p-8 flex flex-col overflow-y-auto">
                    <span class="text-sm font-medium text-[#E86A28] uppercase tracking-wider block mb-2">
                        {{ product.category_name || 'পণ্য' }}
                    </span>
                    <h2 class="text-2xl md:text-3xl font-bold text-[#1E293B] leading-tight mb-2">
                        {{ product.name }}
                    </h2>
                    
                    <div class="flex items-center gap-3 mb-6">
                        <span class="text-2xl font-bold text-[#1E293B]">
                            {{ formatPrice(product.price) }}
                        </span>
                        <span
                            v-if="product.compare_at_price && product.compare_at_price > product.price"
                            class="text-lg text-[#94A3B8] line-through font-medium"
                        >
                            {{ formatPrice(product.compare_at_price) }}
                        </span>
                        <span v-if="product.compare_at_price && product.compare_at_price > product.price" class="px-2 py-1 bg-[#FEE2E2] text-[#EF4444] text-xs font-bold rounded-md ml-2">
                            ছাড়!
                        </span>
                    </div>

                    <div class="prose prose-sm text-[#64748B] mb-8 leading-relaxed">
                        {{ product.short_description || 'এই পণ্যটির চমৎকার ডিজাইন এবং উন্নত কোয়ালিটি আপনাকে মুগ্ধ করবে। প্রতিদিনের ব্যবহারের জন্য একটি পারফেক্ট পছন্দ।' }}
                    </div>

                    <div class="mt-auto pt-6 border-t border-[#F0EAE1]">
                        <!-- Quantity & Add to Cart -->
                        <div class="flex items-center gap-4 mb-4">
                            <!-- Quantity -->
                            <div class="flex items-center border border-[#EAE3D6] rounded-xl bg-[#FAF7F2] h-12">
                                <button
                                    @click="quantity > 1 ? quantity-- : null"
                                    type="button"
                                    class="w-10 h-full flex items-center justify-center text-[#64748B] hover:text-[#1E293B] hover:bg-white rounded-l-xl transition-colors"
                                >
                                    <Minus class="w-4 h-4" />
                                </button>
                                <span class="w-10 text-center font-bold text-[#1E293B]">
                                    {{ quantity }}
                                </span>
                                <button
                                    @click="quantity++"
                                    type="button"
                                    class="w-10 h-full flex items-center justify-center text-[#64748B] hover:text-[#1E293B] hover:bg-white rounded-r-xl transition-colors"
                                >
                                    <Plus class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Add to Cart -->
                            <button
                                @click="handleAddToCart"
                                type="button"
                                class="flex-1 h-12 bg-[#E86A28] hover:bg-[#D35B1D] text-white rounded-xl font-bold text-sm sm:text-base flex items-center justify-center gap-2 transition-all shadow-sm"
                            >
                                <ShoppingBag class="w-5 h-5" />
                                <span>কার্টে যোগ করুন</span>
                            </button>
                        </div>

                        <!-- Wishlist -->
                        <button
                            @click="toggleWishlist(product)"
                            type="button"
                            class="w-full h-12 border border-[#EAE3D6] bg-white hover:bg-[#FAF7F2] text-[#475569] hover:text-[#1E293B] rounded-xl font-semibold text-sm flex items-center justify-center gap-2 transition-all"
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
        </div>
    </Transition>
</template>
