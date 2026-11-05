<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { KeyRound, Phone, ArrowRight, CheckCircle2 } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';
import { useStorefrontPageShell } from '../../Composables/useStorefrontPageShell';

const page = usePage();
const { t } = useI18n();
const { pageClass, panelClass, primaryButtonClass, accentTextClass, inkClass, mutedClass, fieldClass } = useStorefrontPageShell();

const form = useForm({
    login: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head :title="t('auth.forgot_title')" />

    <div :class="[pageClass, 'justify-between']">
        <Header />

        <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full">
                <div :class="[panelClass, 'p-8 sm:p-10']">
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 border flex items-center justify-center mx-auto mb-4" :class="[accentTextClass, panelClass]">
                            <KeyRound class="w-7 h-7" />
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('auth.forgot_title') }}</h1>
                        <p class="text-sm mt-2" :class="mutedClass">{{ t('auth.forgot_intro') }}</p>
                    </div>

                    <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                        <span class="text-sm font-semibold">{{ page.props.flash.success }}</span>
                    </div>

                    <form class="space-y-5" @submit.prevent="submit">
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('auth.login_field') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <Phone class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.login"
                                    type="text"
                                    required
                                    :placeholder="t('auth.login_placeholder')"
                                    :class="[fieldClass, 'pl-10 pr-4']"
                                />
                            </div>
                            <p v-if="form.errors.login" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.login }}</p>
                        </div>

                        <button type="submit" :disabled="form.processing" :class="[primaryButtonClass, 'w-full h-12 text-sm cursor-pointer']">
                            <span>{{ form.processing ? t('auth.sending') : t('auth.forgot_submit') }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-[#F0EAE1] text-center text-sm text-[#64748B]">
                        <Link href="/login" class="font-bold hover:underline" :class="accentTextClass">{{ t('auth.sign_in') }}</Link>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
