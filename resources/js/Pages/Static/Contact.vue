<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Mail, Phone, MapPin } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
</script>

<template>
    <Head title="যোগাযোগ - Nobo Shop" />
    
    <div class="min-h-screen flex flex-col bg-[#FAF7F2] font-sans selection:bg-[#E86A28] selection:text-white">
        <Header />
        
        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div class="bg-white rounded-3xl shadow-sm border border-[#EAE3D6] p-8 md:p-12">
                <h1 class="text-3xl font-bold text-[#1E293B] mb-8 text-center">যোগাযোগ করুন</h1>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="space-y-8">
                        <div>
                            <h2 class="text-xl font-semibold text-[#1E293B] mb-4">আমাদের সাথে কথা বলুন</h2>
                            <p class="text-[#64748B] text-[15px] leading-relaxed mb-6">
                                যেকোনো জিজ্ঞাসা, পরামর্শ বা সহায়তার জন্য আমাদের সাথে নির্দ্বিধায় যোগাযোগ করুন। আমরা দ্রুততম সময়ে আপনার উত্তর দেওয়ার চেষ্টা করবো।
                            </p>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-[#FAF7F2] flex items-center justify-center text-[#E86A28] shrink-0">
                                    <MapPin class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-[#1E293B]">ঠিকানা</h3>
                                    <p class="text-[#64748B] text-sm mt-1">৯২৩, নব শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-[#FAF7F2] flex items-center justify-center text-[#E86A28] shrink-0">
                                    <Phone class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-[#1E293B]">ফোন নম্বর</h3>
                                    <p class="text-[#64748B] text-sm mt-1">+৮৮০ ১২৩৪ ৫৬৭৮৯০</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-[#FAF7F2] flex items-center justify-center text-[#E86A28] shrink-0">
                                    <Mail class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-[#1E293B]">ইমেইল</h3>
                                    <p class="text-[#64748B] text-sm mt-1">support@noboshop.com</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-[#FAF7F2] p-6 rounded-2xl border border-[#EAE3D6]">
                        <form @submit.prevent class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-[#475569] mb-1">আপনার নাম</label>
                                <input type="text" class="w-full px-4 py-2 border border-[#E2D8C5] rounded-xl focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-none" placeholder="আপনার নাম লিখুন">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#475569] mb-1">ইমেইল অথবা ফোন</label>
                                <input type="text" class="w-full px-4 py-2 border border-[#E2D8C5] rounded-xl focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-none" placeholder="যোগাযোগের মাধ্যম">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#475569] mb-1">বার্তা</label>
                                <textarea rows="4" class="w-full px-4 py-2 border border-[#E2D8C5] rounded-xl focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-none" placeholder="আপনার প্রশ্ন বা মতামত..."></textarea>
                            </div>
                            <button type="button" class="w-full py-3 bg-[#E86A28] text-white font-semibold rounded-xl hover:bg-[#D35B1D] transition-colors">
                                মেসেজ পাঠান
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
