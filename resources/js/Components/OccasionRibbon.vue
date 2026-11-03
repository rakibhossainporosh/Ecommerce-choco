<script setup>
import OccasionMotif from './OccasionMotif.vue';
import { useStoreTheme } from '../Composables/useStoreTheme';

defineProps({
    kind: {
        type: String,
        required: true,
    },
});

const { theme } = useStoreTheme();
</script>

<template>
    <div class="ribbon" :data-theme-skin="theme">
        <span class="side side-start" aria-hidden="true">
            <span class="mark"><OccasionMotif :kind="kind" /></span>
            <span class="mark"><OccasionMotif :kind="kind" variant="b" /></span>
        </span>
        <div class="center">
            <slot />
        </div>
        <span class="side side-end" aria-hidden="true">
            <span class="mark"><OccasionMotif :kind="kind" variant="b" /></span>
            <span class="mark"><OccasionMotif :kind="kind" /></span>
        </span>
    </div>
</template>

<style scoped>
.ribbon {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: 0.75rem;
    min-height: 3.25rem;
    padding: 0.45rem 1.15rem;
    border-radius: 1rem;
    background-color: var(--campaign);
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 18px,
        rgb(255 255 255 / 12%) 18px,
        rgb(255 255 255 / 12%) 19px
    );
    color: var(--ink);
}

.ribbon[data-theme-skin='grove'] {
    border-radius: 0;
    min-height: 2.75rem;
    background-image: none;
    box-shadow: none;
}

.ribbon[data-theme-skin='harbor'] {
    border-radius: 0.15rem;
    min-height: 2.4rem;
    background-image: none;
    border: 1px solid rgb(255 255 255 / 18%);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 0.72rem;
}

.side {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-width: 0;
}

.side-start {
    justify-content: flex-end;
}

.side-end {
    justify-content: flex-start;
}

.mark {
    width: 1.35rem;
    height: 1.35rem;
    flex: none;
}

.center {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.45rem 0.7rem;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1.2;
    text-align: center;
}

@media (max-width: 639px) {
    .ribbon {
        gap: 0.45rem;
        padding-inline: 0.75rem;
    }

    .mark:nth-child(n + 2) {
        display: none;
    }

    .center {
        font-size: 0.82rem;
    }
}
</style>
