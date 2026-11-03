<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { useI18n } from '../../Composables/useI18n';
import OccasionScene from '../OccasionScene.vue';
import OccasionCountdown from '../OccasionCountdown.vue';

defineEmits(['shopNow']);

const { t, tm } = useI18n();
const page = usePage();
const occasion = computed(() => page.props.site_settings?.occasion || null);
const campaignOn = computed(() => Boolean(occasion.value?.sections?.hero));

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

const goToSlide = (index) => {
    currentSlide.value = index;
};

const startAutoPlay = () => {
    stopAutoPlay();
    autoPlayInterval = setInterval(nextSlide, 5500);
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
    <!-- Full-bleed lookbook hero — no inset Warm-style rounded banner -->
    <section v-if="campaignOn" class="relative min-h-[52vh] sm:min-h-[58vh] bg-[#1a2e28]" :data-occasion="occasion.key">
        <div class="absolute inset-0">
            <img v-if="occasion.hero_image" :src="occasion.hero_image" :alt="occasion.lines.hero" class="w-full h-full object-cover opacity-70" />
            <OccasionScene v-else :kind="occasion.key" class="w-full h-full opacity-80" />
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#1a2e28] via-[#1a2e28]/40 to-transparent" />
        <div class="relative z-10 max-w-[88rem] mx-auto px-4 sm:px-6 lg:px-8 min-h-[52vh] sm:min-h-[58vh] flex flex-col justify-end pb-10 sm:pb-12 text-[#f3faf6]">
            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[#9fd6c4]">{{ occasion.label }}</p>
            <h1 class="mt-3 max-w-3xl text-3xl sm:text-5xl lg:text-6xl font-semibold tracking-tight leading-[1.3]" style="font-family: var(--store-display)">
                {{ occasion.lines.hero }}
            </h1>
            <div class="mt-6 flex flex-wrap items-center gap-5">
                <OccasionCountdown v-if="occasion.ends_at" :ends-at="occasion.ends_at" />
                <a
                    href="#lookbook"
                    class="inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.16em] border-b border-[#9fd6c4] pb-1 text-[#f3faf6] hover:text-[#9fd6c4]"
                    @click.prevent="$emit('shopNow')"
                >
                    {{ t('hero.shop_now') }}
                    <ArrowRight class="w-4 h-4" />
                </a>
            </div>
        </div>
    </section>

    <section v-else class="relative min-h-[52vh] sm:min-h-[58vh] bg-[#1a2e28]" @mouseenter="stopAutoPlay" @mouseleave="startAutoPlay">
        <div
            class="absolute inset-0 flex transition-transform duration-1000 ease-out"
            :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
        >
            <div
                v-for="(slide, index) in slides"
                :key="index"
                class="w-full flex-shrink-0 relative"
            >
                <img :src="slide.image" :alt="slide.lead" class="absolute inset-0 w-full h-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-t from-[#1a2e28] via-[#1a2e28]/50 to-[#1a2e28]/20" />
            </div>
        </div>

        <div class="relative z-10 max-w-[88rem] mx-auto px-4 sm:px-6 lg:px-8 min-h-[52vh] sm:min-h-[58vh] flex flex-col justify-end pb-10 sm:pb-12 text-[#f3faf6]">
            <template v-if="slides[currentSlide]">
                <h1 class="max-w-3xl text-3xl sm:text-5xl lg:text-6xl font-semibold tracking-tight leading-[1.3]" style="font-family: var(--store-display)">
                    {{ slides[currentSlide].lead }}
                    <span class="text-[#9fd6c4]">{{ slides[currentSlide].accent }}</span>
                </h1>
                <p class="mt-4 max-w-lg text-sm sm:text-base text-[#c5d9d0] leading-relaxed">{{ slides[currentSlide].subtitle }}</p>
                <a
                    href="#lookbook"
                    class="mt-7 inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.16em] border-b border-[#9fd6c4] pb-1 w-fit hover:text-[#9fd6c4]"
                    @click.prevent="$emit('shopNow')"
                >
                    {{ t('hero.shop_now') }}
                    <ArrowRight class="w-4 h-4" />
                </a>
            </template>

            <div class="mt-10 flex items-center gap-2">
                <button
                    v-for="(_, index) in slides"
                    :key="index"
                    type="button"
                    class="h-px transition-all"
                    :class="currentSlide === index ? 'w-10 bg-[#9fd6c4]' : 'w-5 bg-white/35 hover:bg-white/60'"
                    @click="goToSlide(index)"
                />
            </div>
        </div>
    </section>
</template>
