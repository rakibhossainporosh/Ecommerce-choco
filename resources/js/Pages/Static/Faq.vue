<script setup>
import { Head } from '@inertiajs/vue3';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';

const { t, tm } = useI18n();
</script>

<template>
    <Head :title="t('meta.faq')" />
    
    <div class="min-h-screen flex flex-col bg-[#FAF7F2] font-sans selection:bg-[#E86A28] selection:text-white">
        <Header />
        
        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div class="bg-white rounded-3xl shadow-sm border border-[#EAE3D6] p-8 md:p-12">
                <h1 class="text-3xl font-bold text-[#1E293B] mb-8 text-center">{{ t('pages.faq.title') }}</h1>
                
                <div class="space-y-6">
                    <div
                        v-for="(item, index) in tm('pages.faq.items')"
                        :key="index"
                        class="p-6 border border-[#E2D8C5] rounded-2xl"
                    >
                        <h3 class="text-lg font-semibold text-[#1E293B] mb-2">{{ item.q }}</h3>
                        <p class="text-[#475569] leading-relaxed">{{ item.a }}</p>
                    </div>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
