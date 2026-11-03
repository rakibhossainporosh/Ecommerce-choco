<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';
import { useStorefrontPageShell } from '../../Composables/useStorefrontPageShell';

const page = usePage();
const { t, tm } = useI18n();
const { pageClass, panelClass, inkClass, mutedClass } = useStorefrontPageShell();

const returnWindowDays = computed(() => Number(page.props.site_settings?.return_window_days || 3));

const returnPoints = computed(() => {
    const points = tm('pages.returns.points');

    if (!Array.isArray(points)) {
        return [];
    }

    return points.map((point) =>
        String(point).replace(/:days/g, String(returnWindowDays.value))
    );
});
</script>

<template>
    <Head :title="t('meta.returns')" />

    <div :class="pageClass">
        <Header />

        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div :class="[panelClass, 'p-8 md:p-12']">
                <h1 class="text-3xl font-bold mb-6" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('pages.returns.title') }}</h1>

                <div class="prose max-w-none space-y-6" :class="mutedClass">
                    <p class="text-lg leading-relaxed">{{ t('pages.returns.intro') }}</p>
                    <h2 class="text-xl font-semibold mt-8 mb-4" :class="inkClass">{{ t('pages.returns.rules_title') }}</h2>
                    <ul class="list-disc pl-5 space-y-2">
                        <li v-for="(point, index) in returnPoints" :key="index">{{ point }}</li>
                    </ul>
                    <h2 class="text-xl font-semibold mt-8 mb-4" :class="inkClass">{{ t('pages.returns.refund_title') }}</h2>
                    <p class="leading-relaxed">{{ t('pages.returns.refund') }}</p>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
