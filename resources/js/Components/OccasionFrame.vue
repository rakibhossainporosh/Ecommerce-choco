<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import OccasionMotif from './OccasionMotif.vue';

const page = usePage();
const occasion = computed(() => page.props.site_settings?.occasion || null);
const showGeneral = computed(() => Boolean(occasion.value) && occasion.value.sections?.general !== false);
const marks = ['a', 'b', 'a', 'b', 'a', 'b'];
</script>

<template>
    <div v-if="showGeneral" class="occasion-bar" :data-occasion="occasion.key">
        <div class="occasion-garland" aria-hidden="true">
            <span v-for="(mark, index) in marks" :key="`left-${index}`" class="occasion-mark">
                <OccasionMotif :kind="occasion.key" :variant="mark" />
            </span>
        </div>
        <p>{{ occasion.label }}</p>
        <div class="occasion-garland occasion-garland-right" aria-hidden="true">
            <span v-for="(mark, index) in marks" :key="`right-${index}`" class="occasion-mark">
                <OccasionMotif :kind="occasion.key" :variant="mark" />
            </span>
        </div>
    </div>
</template>

<style scoped>
.occasion-bar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: 0.75rem;
    min-height: 2.5rem;
    padding: 0.3rem 1.25rem;
    overflow: hidden;
}

.occasion-bar p {
    position: relative;
    z-index: 1;
    margin: 0;
    padding: 0 0.35rem;
    text-align: center;
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    line-height: 1.2;
    white-space: nowrap;
}

.occasion-garland {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.85rem;
    min-width: 0;
}

.occasion-garland-right {
    justify-content: flex-start;
}

.occasion-mark {
    width: 0.95rem;
    height: 0.95rem;
    flex: none;
    opacity: 0.92;
}

.occasion-bar[data-occasion='eid_fitr'] {
    color: #f6e7b4;
    background-color: #0c4a3c;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 22px,
        rgb(246 231 180 / 16%) 22px,
        rgb(246 231 180 / 16%) 23px
    );
}

.occasion-bar[data-occasion='eid_adha'] {
    color: #f8e7b0;
    background-color: #3f2a12;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 22px,
        rgb(248 231 176 / 18%) 22px,
        rgb(248 231 176 / 18%) 23px
    );
}

.occasion-bar[data-occasion='durga_puja'] {
    color: #fde68a;
    background-color: #8f1d32;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 22px,
        rgb(253 230 138 / 18%) 22px,
        rgb(253 230 138 / 18%) 23px
    );
}

.occasion-bar[data-occasion='kali_puja'] {
    color: #fde68a;
    background-color: #312e81;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 22px,
        rgb(253 230 138 / 16%) 22px,
        rgb(253 230 138 / 16%) 23px
    );
}

.occasion-bar[data-occasion='noboborsho'] {
    color: #fff7ed;
    background-color: #be123c;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 16px,
        rgb(255 255 255 / 22%) 16px,
        rgb(255 255 255 / 22%) 18px
    );
}

.occasion-bar[data-occasion='christmas'] {
    color: #f8fafc;
    background-color: #166534;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 18px,
        rgb(255 255 255 / 16%) 18px,
        rgb(255 255 255 / 16%) 19px
    );
}

.occasion-bar[data-occasion='halloween'] {
    color: #fdba74;
    background-color: #3f2a14;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 20px,
        rgb(253 186 116 / 18%) 20px,
        rgb(253 186 116 / 18%) 21px
    );
}

.occasion-bar[data-occasion='new_year'] {
    color: #f8e7b0;
    background-color: #0f172a;
    background-image: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 20px,
        rgb(248 231 176 / 18%) 20px,
        rgb(248 231 176 / 18%) 21px
    );
}

@media (max-width: 639px) {
    .occasion-bar {
        gap: 0.45rem;
        padding-inline: 0.65rem;
    }

    .occasion-mark:nth-child(n + 3) {
        display: none;
    }

    .occasion-bar p {
        font-size: 0.75rem;
        letter-spacing: 0.02em;
    }
}
</style>
