<script setup>
import { ArrowRight } from 'lucide-vue-next';

defineEmits(['shopNow']);
</script>

<template>
    <section class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-[#F6F1E8] border border-[#ECE5D8] shadow-[0_4px_24px_rgba(0,0,0,0.02)]">
                <div class="grid grid-cols-1 lg:grid-cols-12 items-center min-h-[380px] lg:min-h-[440px]">
                    <!-- Left Column: Copy & CTA -->
                    <div class="lg:col-span-5 p-8 sm:p-12 lg:pl-14 lg:pr-6 z-10">
                        <h1 class="text-3xl sm:text-4xl lg:text-[44px] font-bold text-[#1E293B] leading-[1.25] tracking-tight">
                            প্রতিদিনের প্রয়োজন,<br />
                            <span class="text-[#1E293B]">আপনার পছন্দ</span>
                        </h1>

                        <p class="mt-4 sm:mt-5 text-[15px] sm:text-base text-[#64748B] leading-relaxed max-w-md">
                            ফ্যাশন, এক্সেসরিজ, হোম ও ইলেকট্রনিক্স — সবকিছু একসাথে
                        </p>

                        <div class="mt-7 sm:mt-8">
                            <a
                                href="#featured"
                                @click.prevent="$emit('shopNow')"
                                class="inline-flex items-center gap-2.5 px-7 py-3.5 bg-[#E86A28] hover:bg-[#D35B1D] text-white text-[15px] font-semibold rounded-full shadow-sm hover:shadow-md transition-all group"
                            >
                                <span>কেনাকাটা করুন</span>
                                <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                            </a>
                        </div>
                    </div>

                    <!-- Right Column: Lifestyle Hero Visual -->
                    <div class="lg:col-span-7 h-[300px] sm:h-[380px] lg:h-full relative overflow-hidden flex items-center justify-end">
                        <img
                            src="/images/storefront/hero-banner.jpg"
                            alt="Nobo Shop Lifestyle Showcase"
                            class="w-full h-full object-cover object-center lg:object-right rounded-b-3xl lg:rounded-b-none lg:rounded-r-3xl"
                        />
                        <!-- Subtle soft gradient overlay on the left side of the image for seamless transition -->
                        <div class="hidden lg:block absolute inset-y-0 left-0 w-28 bg-gradient-to-r from-[#F6F1E8] to-transparent pointer-events-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
