<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { ArrowRight, ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineEmits(['shopNow']);

const slides = [
    {
        image: '/images/storefront/hero-banner.jpg',
        title: 'প্রতিদিনের প্রয়োজন,<br /><span class="text-[#1E293B]">আপনার পছন্দ</span>',
        subtitle: 'ফ্যাশন, এক্সেসরিজ, হোম ও ইলেকট্রনিক্স — সবকিছু একসাথে'
    },
    {
        image: '/images/storefront/hero-2.jpg',
        title: 'অসাধারণ স্টাইল,<br /><span class="text-[#1E293B]">আপনার হাতের মুঠোয়</span>',
        subtitle: 'সেরা মানের গ্যাজেট ও লাইফস্টাইল পণ্য'
    },
    {
        image: '/images/storefront/hero-3.jpg',
        title: 'নতুন কালেকশন,<br /><span class="text-[#1E293B]">এসে গেছে!</span>',
        subtitle: 'স্টাইলিশ পোশাক ও গ্যাজেট দিয়ে সাজিয়ে তুলুন নিজেকে'
    }
];

const currentSlide = ref(0);
let autoPlayInterval = null;

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % slides.length;
};

const prevSlide = () => {
    currentSlide.value = (currentSlide.value - 1 + slides.length) % slides.length;
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
    startAutoPlay();
});

onUnmounted(() => {
    stopAutoPlay();
});
</script>

<template>
    <section class="py-6 sm:py-8" @mouseenter="stopAutoPlay" @mouseleave="startAutoPlay">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-[#F6F1E8] border border-[#ECE5D8] shadow-[0_4px_24px_rgba(0,0,0,0.02)] min-h-[480px] sm:min-h-[600px] lg:min-h-[440px]">
                
                <!-- Slides Container -->
                <div 
                    class="flex transition-transform duration-700 ease-in-out h-full absolute inset-0"
                    :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
                >
                    <div v-for="(slide, index) in slides" :key="index" class="w-full flex-shrink-0 grid grid-cols-1 lg:grid-cols-12 h-full">
                        <!-- Left Column: Copy & CTA -->
                        <div class="lg:col-span-5 p-8 sm:p-12 lg:pl-14 lg:pr-6 z-10 flex flex-col justify-center bg-[#F6F1E8] h-[280px] lg:h-full relative">
                            <h1 class="text-3xl sm:text-4xl lg:text-[44px] font-bold text-[#1E293B] leading-[1.25] tracking-tight" v-html="slide.title"></h1>
                            <p class="mt-4 sm:mt-5 text-[15px] sm:text-base text-[#64748B] leading-relaxed max-w-md">{{ slide.subtitle }}</p>

                            <div class="mt-7 sm:mt-8">
                                <a
                                    href="#featured"
                                    @click.prevent="$emit('shopNow')"
                                    class="inline-flex items-center gap-2.5 px-7 py-3.5 bg-[#E86A28] hover:bg-[#D35B1D] text-white text-[15px] font-semibold rounded-full shadow-sm hover:shadow-md transition-all group w-fit"
                                >
                                    <span>কেনাকাটা করুন</span>
                                    <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                </a>
                            </div>
                        </div>

                        <!-- Right Column: Lifestyle Hero Visual -->
                        <div class="lg:col-span-7 h-[200px] sm:h-[320px] lg:h-full relative overflow-hidden flex items-center justify-end w-full">
                            <img
                                :src="slide.image"
                                :alt="'Slide ' + (index + 1)"
                                class="w-full h-full object-cover object-center lg:object-right rounded-b-3xl lg:rounded-b-none lg:rounded-r-3xl"
                            />
                            <!-- Subtle soft gradient overlay on the left side of the image for seamless transition -->
                            <div class="hidden lg:block absolute inset-y-0 left-0 w-28 bg-gradient-to-r from-[#F6F1E8] to-transparent pointer-events-none"></div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 z-20 pointer-events-none">
                    <button @click="prevSlide" class="w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-[#1E293B] hover:bg-[#E86A28] hover:text-white shadow-sm pointer-events-auto transition-all">
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="absolute inset-y-0 right-0 flex items-center pr-4 z-20 pointer-events-none">
                    <button @click="nextSlide" class="w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-[#1E293B] hover:bg-[#E86A28] hover:text-white shadow-sm pointer-events-auto transition-all">
                        <ChevronRight class="w-5 h-5" />
                    </button>
                </div>

                <!-- Pagination Dots -->
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
                    <button 
                        v-for="(_, index) in slides" 
                        :key="index"
                        @click="goToSlide(index)"
                        class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                        :class="currentSlide === index ? 'bg-[#E86A28] w-8' : 'bg-[#DCCFC0] hover:bg-[#E86A28]/50'"
                        :aria-label="'Go to slide ' + (index + 1)"
                    ></button>
                </div>

            </div>
        </div>
    </section>
</template>
