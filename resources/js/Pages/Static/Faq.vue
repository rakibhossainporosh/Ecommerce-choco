<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
</script>

<template>
    <Head title="সাধারণ জিজ্ঞাসা (FAQ) - Nobo Shop" />
    
    <div class="min-h-screen flex flex-col bg-[#FAF7F2] font-sans selection:bg-[#E86A28] selection:text-white">
        <Header />
        
        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div class="bg-white rounded-3xl shadow-sm border border-[#EAE3D6] p-8 md:p-12">
                <h1 class="text-3xl font-bold text-[#1E293B] mb-8 text-center">সাধারণ জিজ্ঞাসা (FAQ)</h1>
                
                <div class="space-y-6">
                    <div class="p-6 border border-[#E2D8C5] rounded-2xl">
                        <h3 class="text-lg font-semibold text-[#1E293B] mb-2">১. ডেলিভারি হতে কত সময় লাগে?</h3>
                        <p class="text-[#475569] leading-relaxed">ঢাকার ভেতরে সাধারণত ২৪-৪৮ ঘণ্টা এবং ঢাকার বাইরে ২-৪ দিনের মধ্যে ডেলিভারি করা হয়।</p>
                    </div>
                    
                    <div class="p-6 border border-[#E2D8C5] rounded-2xl">
                        <h3 class="text-lg font-semibold text-[#1E293B] mb-2">২. ডেলিভারি চার্জ কত?</h3>
                        <p class="text-[#475569] leading-relaxed">ঢাকার ভেতরে ডেলিভারি চার্জ ৬০ টাকা এবং ঢাকার বাইরে ১২০ টাকা।</p>
                    </div>
                    
                    <div class="p-6 border border-[#E2D8C5] rounded-2xl">
                        <h3 class="text-lg font-semibold text-[#1E293B] mb-2">৩. কিভাবে অর্ডার ট্র্যাক করবো?</h3>
                        <p class="text-[#475569] leading-relaxed">ওয়েবসাইটের উপরের মেন্যু থেকে 'ট্র্যাক অর্ডার' এ ক্লিক করে আপনার অর্ডার নম্বর দিয়ে স্ট্যাটাস চেক করতে পারবেন।</p>
                    </div>
                    
                    <div class="p-6 border border-[#E2D8C5] rounded-2xl">
                        <h3 class="text-lg font-semibold text-[#1E293B] mb-2">৪. ক্যাশ অন ডেলিভারি কি এভেইলএবল?</h3>
                        <p class="text-[#475569] leading-relaxed">হ্যাঁ, আমরা ক্যাশ অন ডেলিভারি সাপোর্ট করি। আপনি প্রোডাক্ট হাতে পেয়ে টাকা দিতে পারবেন।</p>
                    </div>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
