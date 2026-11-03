import { computed } from 'vue';
import { useStoreTheme } from './useStoreTheme';

export function useStorefrontPageShell() {
    const { theme } = useStoreTheme();

    const pageClass = computed(() => {
        if (theme.value === 'grove') {
            return 'store-page min-h-screen bg-[#f3efe8] text-[#1a2e28] flex flex-col font-hind-siliguri';
        }
        if (theme.value === 'harbor') {
            return 'store-page min-h-screen bg-[#f4f7fb] text-[#0f172a] flex flex-col font-hind-siliguri';
        }

        return 'store-page min-h-screen bg-[#FDFBF7] text-[#1E293B] flex flex-col font-hind-siliguri';
    });

    const panelClass = computed(() => {
        if (theme.value === 'grove') {
            return 'bg-white border border-[#1a2e28]/12 shadow-none';
        }
        if (theme.value === 'harbor') {
            return 'bg-white border border-[#d5e0ee] rounded-sm shadow-[0_8px_24px_rgb(15_23_42/0.05)]';
        }

        return 'bg-white border border-[#EAE3D6] rounded-3xl shadow-sm';
    });

    const fieldClass = computed(() => {
        if (theme.value === 'grove') {
            return 'w-full border border-[#1a2e28]/15 bg-[#f3efe8]/60 px-4 py-3 text-sm outline-none focus:border-[#0e6b58] focus:bg-white transition-colors';
        }
        if (theme.value === 'harbor') {
            return 'w-full border border-[#d5e0ee] rounded-sm bg-[#f4f7fb] px-4 py-3 text-sm outline-none focus:border-[#1d4ed8] focus:bg-white transition-colors';
        }

        return 'w-full border border-[#E2D9CD] rounded-xl px-4 py-3 bg-[#FAF7F2] focus:ring-2 focus:ring-[#E86A28]/20 focus:border-[#E86A28] transition-all outline-none';
    });

    const primaryButtonClass = computed(() => {
        if (theme.value === 'grove') {
            return 'inline-flex items-center justify-center gap-2 bg-[#1a2e28] hover:bg-[#0e6b58] text-white font-semibold uppercase tracking-[0.12em] text-[11px] transition-colors disabled:opacity-70';
        }
        if (theme.value === 'harbor') {
            return 'inline-flex items-center justify-center gap-2 bg-[#1d4ed8] hover:bg-[#1e40af] text-white font-black uppercase tracking-[0.1em] text-xs rounded-sm transition-colors disabled:opacity-70';
        }

        return 'inline-flex items-center justify-center gap-2 bg-[#E86A28] hover:bg-[#D35B1D] text-white font-bold rounded-xl shadow-md transition-all disabled:opacity-70';
    });

    const accentTextClass = computed(() => {
        if (theme.value === 'grove') {
            return 'text-[#0e6b58]';
        }
        if (theme.value === 'harbor') {
            return 'text-[#1d4ed8]';
        }

        return 'text-[#E86A28]';
    });

    const inkClass = computed(() => {
        if (theme.value === 'grove') {
            return 'text-[#1a2e28]';
        }
        if (theme.value === 'harbor') {
            return 'text-[#0f172a]';
        }

        return 'text-[#1E293B]';
    });

    const mutedClass = computed(() => {
        if (theme.value === 'grove') {
            return 'text-[#5c6f67]';
        }
        if (theme.value === 'harbor') {
            return 'text-[#3e516b]';
        }

        return 'text-[#64748B]';
    });

    const softPanelClass = computed(() => {
        if (theme.value === 'grove') {
            return 'bg-[#f3efe8]/80 border border-[#1a2e28]/10';
        }
        if (theme.value === 'harbor') {
            return 'bg-[#f4f7fb] border border-[#d5e0ee] rounded-sm';
        }

        return 'bg-[#FAF7F2] border border-[#EAE3D6] rounded-2xl';
    });

    const choiceSelectedClass = computed(() => {
        if (theme.value === 'grove') {
            return 'border-[#0e6b58] bg-[#eef6f3] ring-1 ring-[#0e6b58]/25';
        }
        if (theme.value === 'harbor') {
            return 'border-[#1d4ed8] bg-[#eff6ff] ring-1 ring-[#1d4ed8]/25 rounded-sm';
        }

        return 'border-[#E86A28] bg-[#FFF7F2] ring-1 ring-[#E86A28]/30';
    });

    const choiceIdleClass = computed(() => {
        if (theme.value === 'grove') {
            return 'border-[#1a2e28]/12 bg-white hover:border-[#0e6b58]/40';
        }
        if (theme.value === 'harbor') {
            return 'border-[#d5e0ee] bg-white hover:border-[#1d4ed8]/40 rounded-sm';
        }

        return 'border-[#EAE3D6] bg-white hover:border-[#E2D9CD]';
    });

    const tabActiveClass = computed(() => {
        if (theme.value === 'grove') {
            return 'bg-[#1a2e28] text-white';
        }
        if (theme.value === 'harbor') {
            return 'bg-[#1d4ed8] text-white rounded-sm';
        }

        return 'bg-[#E86A28] text-white shadow-sm rounded-xl';
    });

    const tabIdleClass = computed(() => {
        if (theme.value === 'grove') {
            return 'bg-white text-[#5c6f67] hover:text-[#1a2e28] border border-[#1a2e28]/12';
        }
        if (theme.value === 'harbor') {
            return 'bg-white text-[#3e516b] hover:text-[#0f172a] border border-[#d5e0ee] rounded-sm';
        }

        return 'bg-white text-[#64748B] hover:text-[#1E293B] border border-[#EAE3D6] rounded-xl';
    });

    return {
        theme,
        pageClass,
        panelClass,
        fieldClass,
        primaryButtonClass,
        accentTextClass,
        inkClass,
        mutedClass,
        softPanelClass,
        choiceSelectedClass,
        choiceIdleClass,
        tabActiveClass,
        tabIdleClass,
    };
}
