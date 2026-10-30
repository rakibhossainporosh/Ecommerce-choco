<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserPlus, User, Phone, Mail, Lock, ArrowRight } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';

const form = useForm({
    name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="কাস্টমার রেজিস্ট্রেশন - Nobo Shop" />

    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col justify-between">
        <Header />

        <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full">
                <!-- Card -->
                <div class="bg-white rounded-3xl border border-[#EAE3D6] p-8 sm:p-10 shadow-sm">
                    <!-- Heading -->
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 bg-[#FAF7F2] text-[#E86A28] border border-[#EAE3D6] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-2xs">
                            <UserPlus class="w-7 h-7" />
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#1E293B]">নতুন অ্যাকাউন্ট খুলুন</h1>
                        <p class="text-sm text-[#64748B] mt-2">অ্যাকাউন্ট তৈরি করে দ্রুত চেকআউট ও অর্ডার হিস্ট্রি উপভোগ করুন</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Full Name -->
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">আপনার সম্পূর্ণ নাম *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <User class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="যেমন: মোঃ সাব্বির আহমেদ"
                                    class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                            </div>
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.name }}</p>
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">মোবাইল নম্বর *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <Phone class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    placeholder="01XXXXXXXXX"
                                    class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                            </div>
                            <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.phone }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">ইমেইল ঠিকানা (ঐচ্ছিক)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <Mail class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="user@example.com"
                                    class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.email }}</p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">পাসওয়ার্ড * (কমপক্ষে ৬ অক্ষর)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <Lock class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    required
                                    placeholder="••••••••"
                                    class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                            </div>
                            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.password }}</p>
                        </div>

                        <!-- Password Confirmation -->
                        <div>
                            <label class="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">পাসওয়ার্ড নিশ্চিত করুন *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8]">
                                    <Lock class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    placeholder="••••••••"
                                    class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-[#EAE3D6] focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-hidden bg-[#FAF7F2] focus:bg-white transition-all"
                                />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full h-12 bg-[#E86A28] hover:bg-[#D35B1D] text-white rounded-xl font-bold text-sm flex items-center justify-center gap-2 transition-all shadow-md disabled:opacity-50 cursor-pointer"
                            >
                                <span>{{ form.processing ? 'অ্যাকাউন্ট তৈরি হচ্ছে...' : 'নিবন্ধন সম্পন্ন করুন' }}</span>
                                <ArrowRight class="w-4 h-4" />
                            </button>
                        </div>
                    </form>

                    <!-- Login Link -->
                    <div class="mt-8 pt-6 border-t border-[#F0EAE1] text-center text-sm text-[#64748B]">
                        ইতিমধ্যে অ্যাকাউন্ট আছে?
                        <Link href="/login" class="font-bold text-[#E86A28] hover:underline ml-1">
                            লগইন করুন
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
