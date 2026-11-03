import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const catalog = ['warm', 'grove', 'harbor'];

export function useStoreTheme() {
    const page = usePage();

    const theme = computed(() => {
        const key = page.props.site_settings?.theme;
        return typeof key === 'string' && catalog.includes(key) ? key : 'warm';
    });

    const isWarm = computed(() => theme.value === 'warm');
    const isGrove = computed(() => theme.value === 'grove');
    const isHarbor = computed(() => theme.value === 'harbor');

    return {
        theme,
        isWarm,
        isGrove,
        isHarbor,
    };
}

export function resolveThemeComponent(map) {
    const { theme } = useStoreTheme();

    return computed(() => map[theme.value] || map.warm);
}
