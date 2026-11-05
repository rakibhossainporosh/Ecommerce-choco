import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import SeoLayout from './Components/SeoLayout.vue';

const fallbackStoreName = () => {
    const fromMeta = document.querySelector('meta[name="store-name"]')?.getAttribute('content');

    if (typeof fromMeta === 'string' && fromMeta.trim() !== '') {
        return fromMeta.trim();
    }

    return 'Nobo Shop';
};

let storeName = fallbackStoreName();

const resolveStoreName = (page) => {
    const fromProps = page?.props?.site_settings?.site_name;

    if (typeof fromProps === 'string' && fromProps.trim() !== '') {
        return fromProps.trim();
    }

    return storeName || fallbackStoreName();
};

const applyStoreTheme = (page) => {
    const theme = page?.props?.site_settings?.theme;
    if (typeof theme === 'string' && theme !== '') {
        document.documentElement.dataset.storeTheme = theme;
    }
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${storeName}` : storeName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')).then((module) => {
        if (module.default && module.default.layout === undefined) {
            module.default.layout = SeoLayout;
        }

        return module;
    }),
    setup({ el, App, props, plugin }) {
        storeName = resolveStoreName(props.initialPage);
        applyStoreTheme(props.initialPage);

        router.on('success', (event) => {
            storeName = resolveStoreName(event.detail.page);
            applyStoreTheme(event.detail.page);
        });

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: 'var(--store-brand, #E86A28)',
    },
});
