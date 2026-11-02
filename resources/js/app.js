import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const applyStoreTheme = (page) => {
    const theme = page?.props?.site_settings?.theme;
    if (typeof theme === 'string' && theme !== '') {
        document.documentElement.dataset.storeTheme = theme;
    }
};

createInertiaApp({
    title: (title) => (title ? `${title} - Nobo Shop` : 'Nobo Shop'),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        applyStoreTheme(props.initialPage);
        router.on('success', (event) => applyStoreTheme(event.detail.page));

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: 'var(--store-brand, #E86A28)',
    },
});
