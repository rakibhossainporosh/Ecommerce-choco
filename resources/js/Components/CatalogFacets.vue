<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '../Composables/useI18n';
import { useStoreTheme } from '../Composables/useStoreTheme';

const props = defineProps({
    brands: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update']);
const { t } = useI18n();
const { theme } = useStoreTheme();

const brand = ref(props.filters.brand || '');
const minPrice = ref(props.filters.min_price ?? '');
const maxPrice = ref(props.filters.max_price ?? '');
const inStock = ref(!!props.filters.in_stock);

watch(
    () => props.filters,
    (next) => {
        brand.value = next.brand || '';
        minPrice.value = next.min_price ?? '';
        maxPrice.value = next.max_price ?? '';
        inStock.value = !!next.in_stock;
    },
    { deep: true },
);

const apply = () => {
    emit('update', {
        brand: brand.value || '',
        min_price: minPrice.value === '' ? null : Number(minPrice.value),
        max_price: maxPrice.value === '' ? null : Number(maxPrice.value),
        in_stock: inStock.value,
    });
};

const clear = () => {
    brand.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    inStock.value = false;
    emit('update', {
        brand: '',
        min_price: null,
        max_price: null,
        in_stock: false,
    });
};

const wrapClass = () => {
    if (theme.value === 'grove') {
        return 'border border-[#1a2e28]/15 bg-white p-4';
    }
    if (theme.value === 'harbor') {
        return 'border border-[#cbd5e1] bg-white p-4 rounded-sm';
    }

    return 'border border-[#EAE3D6] bg-white p-4 rounded-2xl shadow-sm';
};

const fieldClass = () => {
    if (theme.value === 'grove') {
        return 'w-full h-10 border border-[#1a2e28]/20 px-3 text-sm bg-white';
    }
    if (theme.value === 'harbor') {
        return 'w-full h-10 border border-[#cbd5e1] px-3 text-sm bg-white rounded-sm';
    }

    return 'w-full h-10 border border-[#EAE3D6] px-3 text-sm bg-[#FDFBF7] rounded-xl';
};
</script>

<template>
    <section :class="wrapClass()" class="mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-40">
                <label class="block text-xs font-semibold uppercase tracking-wide text-[#64748B] mb-1.5">{{ t('home.filter_brand') }}</label>
                <select v-model="brand" :class="fieldClass()" @change="apply">
                    <option value="">{{ t('home.filter_brand_all') }}</option>
                    <option v-for="item in brands" :key="item.id" :value="item.slug">{{ item.name }}</option>
                </select>
            </div>

            <div class="w-[calc(50%-0.375rem)] sm:w-28">
                <label class="block text-xs font-semibold uppercase tracking-wide text-[#64748B] mb-1.5">{{ t('home.filter_min_price') }}</label>
                <input v-model="minPrice" type="number" min="0" inputmode="numeric" :class="fieldClass()" :placeholder="t('home.filter_min_placeholder')" @change="apply">
            </div>

            <div class="w-[calc(50%-0.375rem)] sm:w-28">
                <label class="block text-xs font-semibold uppercase tracking-wide text-[#64748B] mb-1.5">{{ t('home.filter_max_price') }}</label>
                <input v-model="maxPrice" type="number" min="0" inputmode="numeric" :class="fieldClass()" :placeholder="t('home.filter_max_placeholder')" @change="apply">
            </div>

            <label class="inline-flex items-center gap-2 h-10 text-sm text-[#475569] cursor-pointer">
                <input v-model="inStock" type="checkbox" class="h-4 w-4" @change="apply">
                <span>{{ t('home.filter_in_stock') }}</span>
            </label>

            <button type="button" class="h-10 px-4 text-sm font-semibold text-[#E86A28] hover:underline" @click="clear">
                {{ t('home.filter_clear') }}
            </button>
        </div>
    </section>
</template>
