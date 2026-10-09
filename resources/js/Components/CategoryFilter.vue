<script setup>
import { computed } from 'vue';
import { 
    Shirt, 
    Watch, 
    Home, 
    Headphones, 
    ShoppingBag, 
    ChevronDown, 
    Sparkles 
} from 'lucide-vue-next';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    activeCategory: {
        type: String,
        default: 'all',
    },
    activeSort: {
        type: String,
        default: 'newest',
    },
});

const emit = defineEmits(['selectCategory', 'selectSort']);

// Icon mapping based on category slug
const getCategoryIcon = (slug) => {
    switch (slug) {
        case 'fashion':
            return Shirt;
        case 'accessories':
            return Watch;
        case 'home':
            return Home;
        case 'electronics':
            return Headphones;
        case 'bags':
            return ShoppingBag;
        default:
            return Sparkles;
    }
};

const sortOptions = [
    { label: 'নতুন', value: 'newest' },
    { label: 'মূল্য: কম থেকে বেশি', value: 'price_asc' },
    { label: 'মূল্য: বেশি থেকে কম', value: 'price_desc' },
];

const currentSortLabel = computed(() => {
    const found = sortOptions.find(o => o.value === props.activeSort);
    return found ? found.label : 'নতুন';
});
</script>

<template>
    <div id="featured" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Section Heading -->
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-[#1E293B] tracking-tight">
                    নির্বাচিত পণ্য
                </h2>
            </div>

            <!-- Category Pills Bar matching screenshot -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 lg:pb-0 scrollbar-none">
                <!-- All category option -->
                <button
                    @click="$emit('selectCategory', 'all')"
                    :class="[
                        'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all shrink-0',
                        activeCategory === 'all'
                            ? 'bg-white border-2 border-[#E86A28] text-[#E86A28] shadow-sm font-semibold'
                            : 'bg-white/80 border border-[#EAE3D6] text-[#475569] hover:bg-white hover:border-[#CBD5E1]'
                    ]"
                >
                    <Sparkles class="w-4 h-4" />
                    <span>সবগুলো</span>
                </button>

                <!-- Dynamic Categories matching screenshot -->
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="$emit('selectCategory', cat.slug)"
                    :class="[
                        'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all shrink-0',
                        activeCategory === cat.slug
                            ? 'bg-white border-2 border-[#E86A28] text-[#E86A28] shadow-sm font-semibold'
                            : 'bg-white/80 border border-[#EAE3D6] text-[#475569] hover:bg-white hover:border-[#CBD5E1]'
                    ]"
                >
                    <component :is="getCategoryIcon(cat.slug)" class="w-4 h-4 text-inherit" />
                    <span>{{ cat.name }}</span>
                </button>
            </div>

            <!-- Sort By Dropdown -->
            <div class="flex items-center justify-end shrink-0">
                <div class="relative inline-block text-left group">
                    <div class="inline-flex items-center gap-2 text-sm text-[#475569] font-medium bg-white px-3.5 py-2 rounded-full border border-[#EAE3D6] hover:border-[#CBD5E1] transition-colors cursor-pointer">
                        <span class="text-[#64748B]">সাজান:</span>
                        <span class="text-[#1E293B] font-semibold">{{ currentSortLabel }}</span>
                        <ChevronDown class="w-4 h-4 text-[#94A3B8]" />
                    </div>

                    <select
                        :value="activeSort"
                        @change="$emit('selectSort', $event.target.value)"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    >
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>
