<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useI18n } from '../../Composables/useI18n';
import OccasionScene from '../OccasionScene.vue';
import OccasionCountdown from '../OccasionCountdown.vue';

defineEmits(['shopNow']);

const { t, tm } = useI18n();
const page = usePage();
const occasion = computed(() => page.props.site_settings?.occasion || null);
const campaignOn = computed(() => Boolean(occasion.value?.sections?.hero));
const siteName = computed(() => page.props.site_settings?.site_name || 'Nobo Shop');

const imagePaths = [
    '/images/storefront/hero-banner.jpg',
    '/images/storefront/hero-2.jpg',
    '/images/storefront/hero-3.jpg',
];

const slides = computed(() => {
    const copySlides = tm('hero.slides') || [];

    return copySlides.map((slide, index) => ({
        lead: slide.lead,
        accent: slide.accent,
        subtitle: slide.subtitle,
        image: index === 0 && campaignOn.value && occasion.value?.hero_image
            ? occasion.value.hero_image
            : (imagePaths[index] || imagePaths[0]),
    }));
});

const currentSlide = ref(0);
let autoPlayInterval = null;

const nextSlide = () => {
    if (slides.value.length === 0) {
        return;
    }
    currentSlide.value = (currentSlide.value + 1) % slides.value.length;
};

const prevSlide = () => {
    if (slides.value.length === 0) {
        return;
    }
    currentSlide.value = (currentSlide.value - 1 + slides.value.length) % slides.value.length;
};

const goToSlide = (index) => {
    currentSlide.value = index;
};

const startAutoPlay = () => {
    stopAutoPlay();
    autoPlayInterval = setInterval(nextSlide, 4500);
};

const stopAutoPlay = () => {
    if (autoPlayInterval) {
        clearInterval(autoPlayInterval);
    }
};

onMounted(() => {
    if (!campaignOn.value) {
        startAutoPlay();
    }
});

onUnmounted(() => {
    stopAutoPlay();
});
</script>

<template>
    <section v-if="campaignOn" class="bg-[#f4f7fb]">
        <div class="max-w-[90rem] mx-auto px-0 sm:px-0">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-0 min-h-[22rem] border border-[#d5e0ee] rounded-sm overflow-hidden bg-white shadow-[0_10px_30px_rgb(15_23_42/0.06)]" :data-occasion="occasion.key">
                <div class="lg:col-span-4 p-5 sm:p-7 flex flex-col justify-center border-b lg:border-b-0 lg:border-r border-[#e7eef8]">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#1d4ed8]">{{ occasion.label }}</p>
                    <h1 class="mt-3 text-3xl font-black tracking-tight text-[#0f172a] leading-tight">{{ occasion.lines.hero }}</h1>
                    <div class="mt-4">
                        <OccasionCountdown v-if="occasion.ends_at" :ends-at="occasion.ends_at" />
                    </div>
                    <a
                        href="#featured"
                        class="mt-6 inline-flex items-center justify-center gap-2 h-11 px-5 bg-[#1d4ed8] hover:bg-[#1e40af] text-white text-xs font-black uppercase tracking-[0.1em] rounded-sm w-fit"
                        @click.prevent="$emit('shopNow')"
                    >
                        {{ t('hero.shop_now') }}
                        <ArrowRight class="w-4 h-4" />
                    </a>
                </div>
                <div class="lg:col-span-8 relative min-h-[16rem] bg-[#1e3a8a]">
                    <img v-if="occasion.hero_image" :src="occasion.hero_image" :alt="occasion.lines.hero" class="absolute inset-0 w-full h-full object-cover" />
                    <OccasionScene v-else :kind="occasion.key" />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a]/35 to-transparent pointer-events-none" />
                </div>
            </div>
        </div>
    </section>

    <section v-else class="bg-[#f4f7fb]" @mouseenter="stopAutoPlay" @mouseleave="startAutoPlay">
        <div class="relative overflow-hidden rounded-sm border border-[#d5e0ee] bg-white min-h-0 lg:min-h-[20rem] shadow-[0_10px_30px_rgb(15_23_42/0.06)]">
            <div
                class="flex transition-transform duration-500 relative lg:absolute lg:inset-0"
                :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
            >
                <div
                    v-for="(slide, index) in slides"
                    :key="index"
                    class="w-full flex-shrink-0 grid grid-cols-1 lg:grid-cols-2 lg:h-full"
                >
                    <div class="p-6 sm:p-8 pb-12 flex flex-col justify-center order-2 lg:order-1">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#1d4ed8]">{{ siteName }}</p>
                        <h1 class="mt-3 text-3xl sm:text-4xl font-black text-[#0f172a] leading-tight">
                            {{ slide.lead }} <span class="text-[#1d4ed8]">{{ slide.accent }}</span>
                        </h1>
                        <p class="mt-3 text-sm text-[#3e516b] max-w-md leading-relaxed">{{ slide.subtitle }}</p>
                        <a
                            href="#featured"
                            class="mt-6 inline-flex items-center justify-center gap-2 h-11 px-5 w-fit bg-[#1d4ed8] hover:bg-[#1e40af] text-white text-xs font-black uppercase tracking-[0.1em] rounded-sm"
                            @click.prevent="$emit('shopNow')"
                        >
                            {{ t('hero.shop_now') }}
                            <ArrowRight class="w-4 h-4" />
                        </a>
                    </div>
                    <div class="relative min-h-[14rem] lg:min-h-full order-1 lg:order-2 bg-[#e7eef8]">
                        <img :src="slide.image" :alt="slide.lead" class="absolute inset-0 w-full h-full object-cover" />
                    </div>
                </div>
            </div>
            <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 bg-white/95 border border-[#d5e0ee] rounded-sm flex items-center justify-center z-10 shadow-sm hover:bg-white" @click="prevSlide">
                <ChevronLeft class="w-4 h-4" />
            </button>
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 bg-white/95 border border-[#d5e0ee] rounded-sm flex items-center justify-center z-10 shadow-sm hover:bg-white" @click="nextSlide">
                <ChevronRight class="w-4 h-4" />
            </button>
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 flex items-center gap-1.5">
                <button
                    v-for="(_, index) in slides"
                    :key="index"
                    type="button"
                    class="h-1.5 rounded-full transition-all"
                    :class="currentSlide === index ? 'w-6 bg-[#1d4ed8]' : 'w-1.5 bg-[#94a3b8] hover:bg-[#64748b]'"
                    @click="goToSlide(index)"
                />
            </div>
        </div>
    </section>
</template>
