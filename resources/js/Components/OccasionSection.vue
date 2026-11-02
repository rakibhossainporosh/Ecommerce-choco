<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import OccasionRibbon from './OccasionRibbon.vue';

const props = defineProps({
    part: {
        type: String,
        required: true,
    },
});

const page = usePage();
const occasion = computed(() => page.props.site_settings?.occasion || null);
const active = computed(() => Boolean(occasion.value?.sections?.[props.part]));
const bandLabel = computed(() => occasion.value?.lines?.[props.part] || occasion.value?.label || '');
</script>

<template>
    <section v-if="active" class="campaign" :data-occasion="occasion.key" :data-part="part">
        <OccasionRibbon v-if="part !== 'hero'" class="campaign-ribbon" :kind="occasion.key">
            {{ bandLabel }}
        </OccasionRibbon>
        <div class="campaign-body">
            <slot />
        </div>
    </section>
    <slot v-else />
</template>

<style scoped>
.campaign-ribbon {
    width: min(72rem, calc(100% - 2rem));
    margin: 0.85rem auto 1.5rem;
}

.campaign[data-occasion='eid_fitr'] {
    --campaign: #0c4a3c;
    --ink: #f6e7b4;
    --wash: #eef6f3;
}

.campaign[data-occasion='eid_adha'] {
    --campaign: #3f2a12;
    --ink: #f8e7b0;
    --wash: #f7f3ea;
}

.campaign[data-occasion='durga_puja'] {
    --campaign: #8f1d32;
    --ink: #fde68a;
    --wash: #fdf2f4;
}

.campaign[data-occasion='kali_puja'] {
    --campaign: #312e81;
    --ink: #fde68a;
    --wash: #f3f2fb;
}

.campaign[data-occasion='noboborsho'] {
    --campaign: #be123c;
    --ink: #fff7ed;
    --wash: #fff5f6;
}

.campaign[data-occasion='christmas'] {
    --campaign: #166534;
    --ink: #f8fafc;
    --wash: #f1f8f3;
}

.campaign[data-occasion='halloween'] {
    --campaign: #3f2a14;
    --ink: #fdba74;
    --wash: #f8f4ee;
}

.campaign[data-occasion='new_year'] {
    --campaign: #0f172a;
    --ink: #f8e7b0;
    --wash: #f6f4ee;
}
</style>
