<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { MessageCircle } from 'lucide-vue-next';
import { useI18n } from '../Composables/useI18n';

const page = usePage();
const { t } = useI18n();

const href = computed(() => {
    const number = page.props.site_settings?.whatsapp_number;
    if (!number) {
        return null;
    }

    const message = page.props.site_settings?.whatsapp_message || t('whatsapp.default_message');
    const params = new URLSearchParams({ text: message });

    return `https://wa.me/${number}?${params.toString()}`;
});
</script>

<template>
    <a
        v-if="href"
        :href="href"
        target="_blank"
        rel="noopener noreferrer"
        class="fixed z-50 bottom-20 right-4 sm:bottom-6 sm:right-6 inline-flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/15 transition hover:scale-105 hover:bg-[#1ebe57] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#25D366]"
        :aria-label="t('whatsapp.cta')"
    >
        <MessageCircle class="h-6 w-6 sm:h-7 sm:w-7" />
    </a>
</template>
