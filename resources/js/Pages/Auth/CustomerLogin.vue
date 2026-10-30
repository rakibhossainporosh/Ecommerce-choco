<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { LogIn, Phone, Lock, ArrowRight, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';

const page = usePage();

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="কাস্টমার লগইন - Nobo Shop" />

    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col justify-between">
        <Header />

        <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full">
                <!-- Card -->
                <div class="bg-white rounded-3xl border border-[#EAE3D6] p-8 sm:p-10 shadow-sm">
                    <!-- Heading -->
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 bg-[#FAF7F2] text-[#E86A28] border border-[#EAE3D6] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-2xs">
                            <LogIn class="w-7 h-7" />
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#1E293B]">কাস্টমার লগইন</h1>
                        <p class="text-sm text-[#64748B] mt-2">আপনার অ্যাকাউন্ট দিয়ে অর্ডার ও ট্র্যাকিং হিস্ট্রি দেখুন</p>
                    </div>

                    <!-- Flash Message -->
                    <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                        <span class="text-sm font-semibold">{{ page.props.flash.success }}</span>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Phone / Email -->
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">মোবাইল নম্বর অথবা ইমেইল</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <Phone class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.login"
                                    type="text"
                                    required
                                    placeholder="017XXXXXXXX অথবা user@email.com"
                                    class="w-full pl-10 pr-4 py-3 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                            </div>
                            <p v-if="form.errors.login" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.login }}</p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">পাসওয়ার্ড</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <Lock class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    required
                                    placeholder="••••••••"
                                    class="w-full pl-10 pr-4 py-3 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                            </div>
                            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.password }}</p>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between text-sm">
                            <label class="flex items-center gap-2 cursor-pointer text-[#64748B]">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="rounded-sm border-[#EAE3D6] text-[#E86A28] focus:ring-[#E86A28]"
                                />
                                <span>মনে রাখুন</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full h-12 bg-[#E86A28] hover:bg-[#D35B1D] text-white rounded-xl font-bold text-sm flex items-center justify-center gap-2 transition-all shadow-md disabled:opacity-50 cursor-pointer"
                        >
                            <span>{{ form.processing ? 'লগইন হচ্ছে...' : 'লগইন করুন' }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </form>

                    <!-- Register Link -->
                    <div class="mt-8 pt-6 border-t border-[#F0EAE1] text-center text-sm text-[#64748B]">
                        এখনো অ্যাকাউন্ট নেই?
                        <Link href="/register" class="font-bold text-[#E86A28] hover:underline ml-1">
                            নতুন অ্যাকাউন্ট তৈরি করুন
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
