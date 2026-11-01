import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

function readPath(source, key) {
    if (!source || typeof source !== 'object') {
        return undefined;
    }

    return key.split('.').reduce((value, part) => {
        if (value && typeof value === 'object' && part in value) {
            return value[part];
        }

        return undefined;
    }, source);
}

function interpolate(line, replace) {
    return line.replace(/:([A-Za-z0-9_]+)/g, (match, token) => {
        if (replace[token] === undefined || replace[token] === null) {
            return match;
        }

        return String(replace[token]);
    });
}

export function useI18n() {
    const page = usePage();
    const locale = computed(() => page.props.locale || 'en');
    const locales = computed(() => page.props.locales || []);

    const tm = (key) => readPath(page.props.translations, key);

    const t = (key, replace = {}) => {
        const value = tm(key);

        if (typeof value !== 'string') {
            return key;
        }

        return interpolate(value, replace);
    };

    const switchLocale = (nextLocale) => {
        if (!nextLocale || nextLocale === locale.value) {
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/locale';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = token;

        const localeInput = document.createElement('input');
        localeInput.type = 'hidden';
        localeInput.name = 'locale';
        localeInput.value = nextLocale;

        form.append(tokenInput, localeInput);
        document.body.append(form);
        form.submit();
    };

    return { t, tm, locale, locales, switchLocale };
}
