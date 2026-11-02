<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useI18n } from '../Composables/useI18n';
import OccasionScene from './OccasionScene.vue';
import OccasionCountdown from './OccasionCountdown.vue';

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
    autoPlayInterval = setInterval(nextSlide, 5000);
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
    <section v-if="campaignOn" class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="campaign-hero" :data-occasion="occasion.key">
                <div class="campaign-copy">
                    <p>{{ occasion.label }}</p>
                    <h1>{{ occasion.lines.hero }}</h1>
                    <OccasionCountdown v-if="occasion.ends_at" :ends-at="occasion.ends_at" />
                    <a
                        href="#featured"
                        class="shop-now"
                        @click.prevent="$emit('shopNow')"
                    >
                        <span>{{ t('hero.shop_now') }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </a>
                </div>
                <div class="campaign-art">
                    <img v-if="occasion.hero_image" :src="occasion.hero_image" :alt="occasion.lines.hero" />
                    <OccasionScene v-else :kind="occasion.key" />
                </div>
            </div>
        </div>
    </section>
    <section v-else class="py-6 sm:py-8" @mouseenter="stopAutoPlay" @mouseleave="startAutoPlay">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="hero-card relative overflow-hidden rounded-3xl bg-[#F6F1E8] border border-[#ECE5D8] shadow-[0_4px_24px_rgba(0,0,0,0.02)] min-h-[480px] sm:min-h-[600px] lg:min-h-[440px]">
                
                <!-- Slides Container -->
                <div 
                    class="flex transition-transform duration-700 ease-in-out h-full absolute inset-0"
                    :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
                >
                    <div v-for="(slide, index) in slides" :key="index" class="w-full flex-shrink-0 grid grid-cols-1 lg:grid-cols-12 h-full">
                        <!-- Left Column: Copy & CTA -->
                        <div class="lg:col-span-5 p-8 sm:p-12 lg:pl-14 lg:pr-6 z-10 flex flex-col justify-center bg-[#F6F1E8] h-[280px] lg:h-full relative">
                            <h1 class="text-3xl sm:text-4xl lg:text-[44px] font-bold text-[#1E293B] leading-[1.25] tracking-tight">
                                {{ slide.lead }}<br /><span class="text-[#1E293B]">{{ slide.accent }}</span>
                            </h1>
                            <p class="mt-4 sm:mt-5 text-[15px] sm:text-base text-[#64748B] leading-relaxed max-w-md">{{ slide.subtitle }}</p>

                            <div class="mt-7 sm:mt-8">
                                <a
                                    href="#featured"
                                    @click.prevent="$emit('shopNow')"
                                    class="inline-flex items-center gap-2.5 px-7 py-3.5 bg-[#E86A28] hover:bg-[#D35B1D] text-white text-[15px] font-semibold rounded-full shadow-sm hover:shadow-md transition-all group w-fit"
                                >
                                    <span>{{ t('hero.shop_now') }}</span>
                                    <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                </a>
                            </div>
                        </div>

                        <!-- Right Column: Lifestyle Hero Visual -->
                        <div class="lg:col-span-7 h-[200px] sm:h-[320px] lg:h-full relative overflow-hidden flex items-center justify-end w-full">
                            <img
                                :src="slide.image"
                                :alt="t('hero.go_to', { number: index + 1 })"
                                class="w-full h-full object-cover object-center lg:object-right rounded-b-3xl lg:rounded-b-none lg:rounded-r-3xl"
                            />
                            <span v-if="index === 0 && campaignOn" class="hero-ribbon">{{ occasion.lines.hero }}</span>
                            <!-- Subtle soft gradient overlay on the left side of the image for seamless transition -->
                            <div class="hidden lg:block absolute inset-y-0 left-0 w-28 bg-gradient-to-r from-[#F6F1E8] to-transparent pointer-events-none"></div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 z-20 pointer-events-none">
                    <button type="button" @click="prevSlide" :title="t('hero.previous')" class="w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-[#1E293B] hover:bg-[#E86A28] hover:text-white shadow-sm pointer-events-auto transition-all">
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="absolute inset-y-0 right-0 flex items-center pr-4 z-20 pointer-events-none">
                    <button type="button" @click="nextSlide" :title="t('hero.next')" class="w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-[#1E293B] hover:bg-[#E86A28] hover:text-white shadow-sm pointer-events-auto transition-all">
                        <ChevronRight class="w-5 h-5" />
                    </button>
                </div>

                <!-- Pagination Dots -->
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
                    <button 
                        v-for="(_, index) in slides" 
                        :key="index"
                        type="button"
                        @click="goToSlide(index)"
                        class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                        :class="currentSlide === index ? 'bg-[#E86A28] w-8' : 'bg-[#DCCFC0] hover:bg-[#E86A28]/50'"
                        :aria-label="t('hero.go_to', { number: index + 1 })"
                    ></button>
                </div>

            </div>
        </div>
    </section>
</template>

<style scoped>
.hero-ribbon {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 10;
    max-width: 70%;
    padding: 0.28rem 0.7rem;
    border-radius: 999px;
    background: var(--campaign, #3f2a12);
    color: var(--ink, #f8e7b0);
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: 0.02em;
}

.campaign-hero {
    display: grid;
    grid-template-columns: 1fr;
    min-height: 28rem;
    overflow: hidden;
    border-radius: 1.5rem;
    border: 1px solid color-mix(in srgb, var(--campaign) 35%, white);
}

.campaign-copy {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 1rem;
    padding: 1.75rem 1.5rem;
    background: var(--wash);
}

.campaign-copy p {
    margin: 0;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--campaign);
}

.campaign-copy h1 {
    margin: 0;
    max-width: 16ch;
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800;
    line-height: 1.15;
    color: #1e293b;
}

.campaign-copy :deep(.countdown-cell) {
    background: color-mix(in srgb, var(--campaign) 14%, white);
    color: var(--campaign);
}

.shop-now {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    width: fit-content;
    margin-top: 0.25rem;
    padding: 0.8rem 1.4rem;
    border-radius: 999px;
    background: var(--store-brand, #e86a28);
    color: white;
    font-size: 0.95rem;
    font-weight: 700;
}

.campaign-art {
    position: relative;
    min-height: 16rem;
    background: var(--campaign);
}

.campaign-art img,
.campaign-art :deep(svg) {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

@media (min-width: 1024px) {
    .campaign-hero {
        grid-template-columns: minmax(0, 0.86fr) minmax(0, 1.14fr);
        min-height: 28rem;
    }

    .campaign-copy {
        padding: 2.5rem 3rem;
    }

    .campaign-art {
        min-height: 100%;
    }
}
</style>
