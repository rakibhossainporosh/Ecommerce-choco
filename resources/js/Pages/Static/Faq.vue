<script setup>
import { Head } from '@inertiajs/vue3';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';
import { useStorefrontPageShell } from '../../Composables/useStorefrontPageShell';

const { t, tm } = useI18n();
const { pageClass, panelClass, inkClass, mutedClass } = useStorefrontPageShell();
</script>

<template>
    <Head :title="t('meta.faq')" />

    <div :class="pageClass">
        <Header />

        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div :class="[panelClass, 'p-8 md:p-12']">
                <h1 class="text-3xl font-bold mb-8 text-center" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('pages.faq.title') }}</h1>

                <div class="space-y-4">
                    <div
                        v-for="(item, index) in tm('pages.faq.items')"
                        :key="index"
                        class="p-5 border"
                        :class="panelClass"
                    >
                        <h3 class="text-lg font-semibold mb-2" :class="inkClass">{{ item.q }}</h3>
                        <p class="leading-relaxed" :class="mutedClass">{{ item.a }}</p>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
