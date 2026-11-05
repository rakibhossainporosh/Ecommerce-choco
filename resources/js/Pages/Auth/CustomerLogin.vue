<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { LogIn, Phone, Lock, ArrowRight, CheckCircle2, Eye, EyeOff } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';
import { useStorefrontPageShell } from '../../Composables/useStorefrontPageShell';

const page = usePage();
const { t } = useI18n();
const { pageClass, panelClass, primaryButtonClass, accentTextClass, inkClass, mutedClass, fieldClass } = useStorefrontPageShell();

const mode = ref('password');
const showPassword = ref(false);
const otpSent = ref(false);

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const otpForm = useForm({
    login: '',
    code: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

const sendOtp = () => {
    otpForm.transform((data) => ({ login: data.login })).post('/login/otp', {
        preserveScroll: true,
        onSuccess: () => {
            otpSent.value = true;
        },
    });
};

const verifyOtp = () => {
    otpForm.transform((data) => ({
        code: data.code,
        remember: data.remember,
    })).post('/login/otp/verify');
};
</script>

<template>
    <Head :title="t('meta.login')" />

    <div :class="[pageClass, 'justify-between']">
        <Header />

        <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full">
                <div :class="[panelClass, 'p-8 sm:p-10']">
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 border flex items-center justify-center mx-auto mb-4" :class="[accentTextClass, panelClass]">
                            <LogIn class="w-7 h-7" />
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('auth.login_title') }}</h1>
                        <p class="text-sm mt-2" :class="mutedClass">{{ t('auth.login_intro') }}</p>
                    </div>

                    <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                        <span class="text-sm font-semibold">{{ page.props.flash.success }}</span>
                    </div>

                    <div class="mb-6 grid grid-cols-2 gap-2 p-1 rounded-xl bg-black/5">
                        <button
                            type="button"
                            class="h-10 text-xs font-bold uppercase tracking-wider rounded-lg transition-colors"
                            :class="mode === 'password' ? [primaryButtonClass, 'shadow-sm'] : mutedClass"
                            @click="mode = 'password'"
                        >
                            {{ t('auth.password_tab') }}
                        </button>
                        <button
                            type="button"
                            class="h-10 text-xs font-bold uppercase tracking-wider rounded-lg transition-colors"
                            :class="mode === 'otp' ? [primaryButtonClass, 'shadow-sm'] : mutedClass"
                            @click="mode = 'otp'"
                        >
                            {{ t('auth.otp_tab') }}
                        </button>
                    </div>

                    <form v-if="mode === 'password'" class="space-y-5" @submit.prevent="submit">
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
                                    placeholder="••••••••"
                                    autocomplete="current-password"
                                    :class="[fieldClass, 'pl-10 pr-11']"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-[#64748B] cursor-pointer"
                                    :aria-label="showPassword ? t('auth.hide_password') : t('auth.show_password')"
                                    :aria-pressed="showPassword"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff v-if="showPassword" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex items-center justify-between gap-3 text-sm">
                            <label class="flex items-center gap-2 cursor-pointer text-[#64748B]">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="rounded-sm border-current"
                                    :class="accentTextClass"
                                    style="accent-color: currentColor"
                                />
                                <span>{{ t('auth.remember') }}</span>
                            </label>
                            <Link href="/forgot-password" class="font-semibold hover:underline shrink-0" :class="accentTextClass">
                                {{ t('auth.forgot_password') }}
                            </Link>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            :class="[primaryButtonClass, 'w-full h-12 text-sm cursor-pointer']"
                        >
                            <span>{{ form.processing ? t('auth.logging_in') : t('auth.submit_login') }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </form>

                    <form v-else class="space-y-5" @submit.prevent="otpSent ? verifyOtp() : sendOtp()">
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('auth.login_field') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <Phone class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="otpForm.login"
                                    type="text"
                                    required
                                    :placeholder="t('auth.login_placeholder')"
                                    :class="[fieldClass, 'pl-10 pr-4']"
                                />
                            </div>
                            <p v-if="otpForm.errors.login" class="text-xs text-red-500 mt-1 font-medium">{{ otpForm.errors.login }}</p>
                        </div>

                        <div v-if="otpSent">
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">{{ t('auth.otp_code') }}</label>
                            <input
                                v-model="otpForm.code"
                                type="text"
                                inputmode="numeric"
                                maxlength="6"
                                required
                                autocomplete="one-time-code"
                                :class="[fieldClass, 'tracking-[0.3em] text-center text-lg font-bold']"
                            />
                            <p v-if="otpForm.errors.code" class="text-xs text-red-500 mt-1 font-medium">{{ otpForm.errors.code }}</p>
                        </div>

                        <label v-if="otpSent" class="flex items-center gap-2 cursor-pointer text-sm text-[#64748B]">
                            <input
                                v-model="otpForm.remember"
                                type="checkbox"
                                class="rounded-sm border-current"
                                :class="accentTextClass"
                                style="accent-color: currentColor"
                            />
                            <span>{{ t('auth.remember') }}</span>
                        </label>

                        <button
                            type="submit"
                            :disabled="otpForm.processing"
                            :class="[primaryButtonClass, 'w-full h-12 text-sm cursor-pointer']"
                        >
                            <span>
                                {{
                                    otpForm.processing
                                        ? (otpSent ? t('auth.verifying') : t('auth.sending'))
                                        : (otpSent ? t('auth.otp_verify') : t('auth.otp_send'))
                                }}
                            </span>
                            <ArrowRight class="w-4 h-4" />
                        </button>

                        <button
                            v-if="otpSent"
                            type="button"
                            class="w-full text-xs font-semibold hover:underline"
                            :class="accentTextClass"
                            @click="sendOtp"
                        >
                            {{ t('auth.otp_send') }}
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-[#F0EAE1] text-center text-sm text-[#64748B]">
                        {{ t('auth.no_account') }}
                        <Link href="/register" class="font-bold hover:underline ml-1" :class="accentTextClass">
                            {{ t('auth.create_account') }}
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
