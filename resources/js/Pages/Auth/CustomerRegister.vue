<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserPlus, User, Phone, Mail, Lock, ArrowRight, Eye, EyeOff } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';
import { useStorefrontPageShell } from '../../Composables/useStorefrontPageShell';

const { t } = useI18n();
const { pageClass, panelClass, primaryButtonClass, accentTextClass, inkClass, mutedClass, fieldClass } = useStorefrontPageShell();

const form = useForm({
    name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('meta.register')" />

    <div :class="[pageClass, 'justify-between']">
        <Header />

        <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full">
                <div :class="[panelClass, 'p-8 sm:p-10']">
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 border flex items-center justify-center mx-auto mb-4" :class="[accentTextClass, panelClass]">
                            <UserPlus class="w-7 h-7" />
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold" :class="inkClass" style="font-family: var(--store-display, inherit)">{{ t('auth.register_title') }}</h1>
                        <p class="text-sm mt-2" :class="mutedClass">{{ t('auth.register_intro') }}</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('auth.name') }} *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none opacity-50">
                                    <User class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    :placeholder="t('auth.register_name_placeholder')"
                                    :class="[fieldClass, 'pl-10 pr-4']"
                                />
                            </div>
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('auth.phone') }} *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none opacity-50">
                                    <Phone class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    :placeholder="t('auth.phone_placeholder')"
                                    :class="[fieldClass, 'pl-10 pr-4']"
                                />
                            </div>
                            <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('auth.email') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none opacity-50">
                                    <Mail class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="user@example.com"
                                    :class="[fieldClass, 'pl-10 pr-4']"
                                />
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('auth.password_min_hint') }} *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none opacity-50">
                                    <Lock class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    autocomplete="new-password"
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

                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1.5" :class="mutedClass">{{ t('auth.confirm_password') }} *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none opacity-50">
                                    <Lock class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                    :class="[fieldClass, 'pl-10 pr-11']"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-[#64748B] cursor-pointer"
                                    :aria-label="showPasswordConfirmation ? t('auth.hide_password') : t('auth.show_password')"
                                    :aria-pressed="showPasswordConfirmation"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                >
                                    <EyeOff v-if="showPasswordConfirmation" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                :class="[primaryButtonClass, 'w-full h-12 text-sm cursor-pointer']"
                            >
                                <span>{{ form.processing ? t('auth.creating_account') : t('auth.submit_register') }}</span>
                                <ArrowRight class="w-4 h-4" />
                            </button>
                        </div>
                    </form>

                    <div class="mt-8 pt-6 border-t border-[#F0EAE1] text-center text-sm text-[#64748B]">
                        {{ t('auth.have_account') }}
                        <Link href="/login" class="font-bold hover:underline ml-1" :class="accentTextClass">
                            {{ t('auth.sign_in') }}
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
