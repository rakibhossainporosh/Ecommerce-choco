<script setup>
import { computed, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();

const seo = computed(() => {
    const value = page.props?.seo;

    return value && typeof value === 'object' ? value : null;
});

watch(seo, (value) => {
    let node = document.head.querySelector('script[data-store-jsonld]');

    if (!value?.json_ld) {
        node?.remove();

        return;
    }

    if (!node) {
        node = document.createElement('script');
        node.type = 'application/ld+json';
        node.setAttribute('data-store-jsonld', '');
        document.head.appendChild(node);
    }

    node.textContent = JSON.stringify(value.json_ld).replace(/</g, '\\u003c');
}, { immediate: true });
</script>

<template>
    <Head v-if="seo" :title="seo.title || undefined">
        <meta head-key="description" name="description" :content="seo.description" />
        <meta head-key="robots" name="robots" :content="seo.robots" />
        <link head-key="canonical" rel="canonical" :href="seo.canonical" />
        <meta head-key="og:type" property="og:type" :content="seo.type" />
        <meta head-key="og:site_name" property="og:site_name" :content="seo.site_name" />
        <meta head-key="og:locale" property="og:locale" :content="seo.locale" />
        <meta head-key="og:url" property="og:url" :content="seo.canonical" />
        <meta head-key="og:title" property="og:title" :content="seo.document_title" />
        <meta head-key="og:description" property="og:description" :content="seo.description" />
        <meta v-if="seo.image" head-key="og:image" property="og:image" :content="seo.image" />
        <meta v-if="seo.price && seo.currency" head-key="product:price:amount" property="product:price:amount" :content="seo.price" />
        <meta v-if="seo.price && seo.currency" head-key="product:price:currency" property="product:price:currency" :content="seo.currency" />
        <meta head-key="twitter:card" name="twitter:card" :content="seo.image ? 'summary_large_image' : 'summary'" />
        <meta head-key="twitter:title" name="twitter:title" :content="seo.document_title" />
        <meta head-key="twitter:description" name="twitter:description" :content="seo.description" />
        <meta v-if="seo.image" head-key="twitter:image" name="twitter:image" :content="seo.image" />
    </Head>
</template>
