<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from '../Composables/useI18n';

const props = defineProps({
    endsAt: {
        type: String,
        required: true,
    },
});

const { t } = useI18n();
const parts = ref({ d: 0, h: 0, m: 0, s: 0 });
const units = [
    { key: 'd', label: 'home.campaign_days' },
    { key: 'h', label: 'home.campaign_hours' },
    { key: 'm', label: 'home.campaign_minutes' },
    { key: 's', label: 'home.campaign_seconds' },
];

let timer = null;

const tick = () => {
    const end = new Date(props.endsAt).getTime();
    const total = Number.isNaN(end) ? 0 : Math.max(0, Math.floor((end - Date.now()) / 1000));
    parts.value = {
        d: Math.floor(total / 86400),
        h: Math.floor((total % 86400) / 3600),
        m: Math.floor((total % 3600) / 60),
        s: total % 60,
    };
};

const padded = computed(() => ({
    d: String(parts.value.d).padStart(2, '0'),
    h: String(parts.value.h).padStart(2, '0'),
    m: String(parts.value.m).padStart(2, '0'),
    s: String(parts.value.s).padStart(2, '0'),
}));

onMounted(() => {
    tick();
    timer = setInterval(tick, 1000);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});

watch(() => props.endsAt, tick);
</script>

<template>
    <div class="countdown" :aria-label="t('home.campaign_ends')">
        <span v-for="unit in units" :key="unit.key" class="countdown-cell">
            <strong>{{ padded[unit.key] }}</strong>
            <small>{{ t(unit.label) }}</small>
        </span>
    </div>
</template>

<style scoped>
.countdown {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.countdown-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 2.6rem;
    padding: 0.2rem 0.35rem 0.15rem;
    border-radius: 0.55rem;
    background: rgb(255 255 255 / 16%);
}

.countdown-cell strong {
    font-size: 0.95rem;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
}

.countdown-cell small {
    font-size: 0.62rem;
    letter-spacing: 0.03em;
    opacity: 0.85;
}
</style>
