<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    textClass: {
        type: String,
        default: '',
    },
    linkClass: {
        type: String,
        required: true,
    },
});

const page = usePage();

const currentYear = computed(() => new Date().getFullYear());

const brandName = computed(() => {
    const name = page.props.site_settings?.site_name;

    return typeof name === 'string' && name.trim() !== '' ? name.trim() : 'Nobo Shop';
});
</script>

<template>
    <p :class="textClass">
        © {{ currentYear }} {{ brandName }}. All rights reserved.
        Built &amp; maintained by
        <a
            href="https://craftbasetech.com"
            target="_blank"
            rel="noopener noreferrer nofollow"
            class="footer-credit-link"
            :class="linkClass"
            style="text-decoration: none !important; border-bottom: none !important;"
        >
            CraftBase Technologies
        </a>
    </p>
</template>
