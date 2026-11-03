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
    <Head :title="t('meta.terms')" />

    <div :class="pageClass">
        <Header />

        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div :class="[panelClass, 'p-8 md:p-12']">
                <h1 class="text-3xl font-bold mb-6" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('pages.terms.title') }}</h1>

                <div class="prose max-w-none space-y-6" :class="mutedClass">
                    <p class="text-lg leading-relaxed">{{ t('pages.terms.intro') }}</p>
                    <h2 class="text-xl font-semibold mt-8 mb-4" :class="inkClass">{{ t('pages.terms.general_title') }}</h2>
                    <ul class="list-disc pl-5 space-y-2">
                        <li v-for="(point, index) in tm('pages.terms.points')" :key="index">{{ point }}</li>
                    </ul>
                    <h2 class="text-xl font-semibold mt-8 mb-4" :class="inkClass">{{ t('pages.terms.cancel_title') }}</h2>
                    <p class="leading-relaxed">{{ t('pages.terms.cancel') }}</p>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
