<script setup>
import { Check, ShoppingBag, X } from 'lucide-vue-next';
import { useCart } from '../Composables/useCart';
import { useStoreTheme } from '../Composables/useStoreTheme';
import { useI18n } from '../Composables/useI18n';

const { toast, dismissToast, openCart } = useCart();
const { theme } = useStoreTheme();
const { t } = useI18n();

const shellClass = () => {
    if (theme.value === 'grove') {
        return 'bg-[#1a2e28] text-[#f3faf6] border border-white/10';
    }
    if (theme.value === 'harbor') {
        return 'bg-[#0f172a] text-white border border-[#1d4ed8]/40 rounded-sm';
    }

    return 'bg-[#1E293B] text-white rounded-2xl border border-white/10';
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-3"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-if="toast.visible"
                class="fixed bottom-5 left-1/2 z-[70] w-[min(92vw,24rem)] -translate-x-1/2 px-4 py-3 shadow-2xl"
                :class="shellClass()"
                role="status"
            >
                <div class="flex items-center gap-3">
                    <span class="shrink-0 w-9 h-9 inline-flex items-center justify-center bg-white/10">
                        <Check class="w-4 h-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold truncate">{{ toast.title || t('cart.added_title') }}</p>
                        <p v-if="toast.body" class="text-xs opacity-80 truncate">{{ toast.body }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 h-8 px-2.5 text-[10px] font-semibold uppercase tracking-wider inline-flex items-center gap-1 bg-white/15 hover:bg-white/25"
                        @click="openCart(); dismissToast()"
                    >
                        <ShoppingBag class="w-3.5 h-3.5" />
                        {{ t('cart.view') }}
                    </button>
                    <button type="button" class="shrink-0 p-1 opacity-70 hover:opacity-100" @click="dismissToast">
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
