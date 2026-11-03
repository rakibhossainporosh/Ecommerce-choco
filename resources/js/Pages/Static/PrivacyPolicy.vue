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
    <Head :title="t('meta.privacy')" />

    <div :class="pageClass">
        <Header />

        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div :class="[panelClass, 'p-8 md:p-12']">
                <h1 class="text-3xl font-bold mb-6" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('pages.privacy.title') }}</h1>

                <div class="prose max-w-none space-y-6" :class="mutedClass">
                    <p class="text-lg leading-relaxed">{{ t('pages.privacy.intro') }}</p>
                    <div v-for="(section, index) in tm('pages.privacy.sections')" :key="index">
                        <h2 class="text-xl font-semibold mt-8 mb-4" :class="inkClass">{{ section.title }}</h2>
                        <p class="leading-relaxed">{{ section.body }}</p>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
