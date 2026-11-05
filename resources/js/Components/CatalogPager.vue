<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useI18n } from '../Composables/useI18n';
import { useStoreTheme } from '../Composables/useStoreTheme';

const props = defineProps({
    pagination: { type: Object, default: null },
    buildUrl: { type: Function, required: true },
});

const { t } = useI18n();
const { theme } = useStoreTheme();

const visible = computed(() => {
    const last = Number(props.pagination?.last_page || 1);

    return last > 1;
});

const pages = computed(() => {
    const current = Number(props.pagination?.current_page || 1);
    const last = Number(props.pagination?.last_page || 1);
    const windowSize = 2;
    const start = Math.max(1, current - windowSize);
    const end = Math.min(last, current + windowSize);
    const items = [];

    for (let page = start; page <= end; page += 1) {
        items.push(page);
    }

    return items;
});

const tone = computed(() => {
    if (theme.value === 'grove') {
        return {
            wrap: 'border-[#1a2e28]/15 bg-white',
            idle: 'border-[#1a2e28]/20 text-[#1a2e28] hover:border-[#0e6b58] hover:text-[#0e6b58]',
            active: 'border-[#1a2e28] bg-[#1a2e28] text-white',
            muted: 'text-[#5c6f67]',
        };
    }

    if (theme.value === 'harbor') {
        return {
            wrap: 'border-[#cbd5e1] bg-white',
            idle: 'border-[#cbd5e1] text-[#334155] hover:border-[#1d4ed8] hover:text-[#1d4ed8]',
            active: 'border-[#1d4ed8] bg-[#1d4ed8] text-white',
            muted: 'text-[#64748b]',
        };
    }

    return {
        wrap: 'border-[#EAE3D6] bg-white',
        idle: 'border-[#EAE3D6] text-[#475569] hover:border-[#E86A28] hover:text-[#E86A28]',
        active: 'border-[#E86A28] bg-[#E86A28] text-white',
        muted: 'text-[#64748B]',
    };
});
</script>

<template>
    <nav
        v-if="visible"
        class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        :aria-label="t('home.pagination_label')"
    >
        <p class="text-sm" :class="tone.muted">
            {{ t('home.pagination_summary', {
                from: pagination.from || 0,
                to: pagination.to || 0,
                total: pagination.total || 0,
            }) }}
        </p>

        <div class="flex flex-wrap items-center gap-2" :class="tone.wrap">
            <Link
                v-if="pagination.prev_page_url"
                :href="buildUrl(pagination.current_page - 1)"
                preserve-scroll
                class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-semibold transition"
                :class="tone.idle"
            >
                <ChevronLeft class="h-4 w-4" />
                <span class="sr-only sm:not-sr-only sm:ml-1">{{ t('home.pagination_prev') }}</span>
            </Link>
            <span
                v-else
                class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm opacity-40"
                :class="tone.idle"
            >
                <ChevronLeft class="h-4 w-4" />
            </span>

            <Link
                v-for="page in pages"
                :key="page"
                :href="buildUrl(page)"
                preserve-scroll
                class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-semibold transition"
                :class="page === pagination.current_page ? tone.active : tone.idle"
            >
                {{ page }}
            </Link>

            <Link
                v-if="pagination.next_page_url"
                :href="buildUrl(pagination.current_page + 1)"
                preserve-scroll
                class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-semibold transition"
                :class="tone.idle"
            >
                <span class="sr-only sm:not-sr-only sm:mr-1">{{ t('home.pagination_next') }}</span>
                <ChevronRight class="h-4 w-4" />
            </Link>
            <span
                v-else
                class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm opacity-40"
                :class="tone.idle"
            >
                <ChevronRight class="h-4 w-4" />
            </span>
        </div>
    </nav>
</template>
