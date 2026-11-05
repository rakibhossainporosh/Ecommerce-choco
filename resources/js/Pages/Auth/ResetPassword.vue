<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { KeyRound, Lock, ArrowRight, CheckCircle2, Eye, EyeOff } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';
import { useStorefrontPageShell } from '../../Composables/useStorefrontPageShell';

const page = usePage();
const { t } = useI18n();
const { pageClass, panelClass, primaryButtonClass, accentTextClass, inkClass, mutedClass, fieldClass } = useStorefrontPageShell();

const showPassword = ref(false);

const form = useForm({
    code: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('auth.reset_title')" />

    <div :class="[pageClass, 'justify-between']">
        <Header />

        <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full">
                <div :class="[panelClass, 'p-8 sm:p-10']">
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 border flex items-center justify-center mx-auto mb-4" :class="[accentTextClass, panelClass]">
                            <KeyRound class="w-7 h-7" />
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('auth.reset_title') }}</h1>
                        <p class="text-sm mt-2" :class="mutedClass">{{ t('auth.reset_intro') }}</p>
                    </div>

                    <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                        <span class="text-sm font-semibold">{{ page.props.flash.success }}</span>
                    </div>

                    <form class="space-y-5" @submit.prevent="submit">
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('auth.otp_code') }}</label>
                            <input
                                v-model="form.code"
                                type="text"
                                inputmode="numeric"
                                maxlength="6"
                                required
                                autocomplete="one-time-code"
                                :class="[fieldClass, 'tracking-[0.3em] text-center text-lg font-bold']"
                            />
                            <p v-if="form.errors.code" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.code }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('auth.password') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <Lock class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    :class="[fieldClass, 'pl-10 pr-11']"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-[#64748B] cursor-pointer"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff v-if="showPassword" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('auth.confirm_password') }}</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                :class="fieldClass"
                            />
                        </div>

                        <button type="submit" :disabled="form.processing" :class="[primaryButtonClass, 'w-full h-12 text-sm cursor-pointer']">
                            <span>{{ form.processing ? t('auth.verifying') : t('auth.reset_submit') }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-[#F0EAE1] text-center text-sm text-[#64748B]">
                        <Link href="/forgot-password" class="font-bold hover:underline" :class="accentTextClass">{{ t('auth.forgot_submit') }}</Link>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
