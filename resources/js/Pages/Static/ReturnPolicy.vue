<script setup>
import { Head } from '@inertiajs/vue3';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';

const { t, tm } = useI18n();
</script>

<template>
    <Head :title="t('meta.returns')" />
    
    <div class="min-h-screen flex flex-col bg-[#FAF7F2] font-sans selection:bg-[#E86A28] selection:text-white">
        <Header />
        
        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div class="bg-white rounded-3xl shadow-sm border border-[#EAE3D6] p-8 md:p-12">
                <h1 class="text-3xl font-bold text-[#1E293B] mb-6">{{ t('pages.returns.title') }}</h1>
                
                <div class="prose prose-slate max-w-none text-[#475569] space-y-6">
                    <p class="text-lg leading-relaxed">
                        {{ t('pages.returns.intro') }}
                    </p>
                    
                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">{{ t('pages.returns.rules_title') }}</h2>
                    <ul class="list-disc pl-5 space-y-2">
                        <li v-for="(point, index) in tm('pages.returns.points')" :key="index">{{ point }}</li>
                    </ul>

                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">{{ t('pages.returns.refund_title') }}</h2>
                    <p class="leading-relaxed">
                        {{ t('pages.returns.refund') }}
                    </p>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
