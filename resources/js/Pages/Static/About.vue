<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
</script>

<template>
    <Head title="আমাদের সম্পর্কে - Nobo Shop" />
    
    <div class="min-h-screen flex flex-col bg-[#FAF7F2] font-sans selection:bg-[#E86A28] selection:text-white">
        <Header />
        
        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div class="bg-white rounded-3xl shadow-sm border border-[#EAE3D6] p-8 md:p-12">
                <h1 class="text-3xl font-bold text-[#1E293B] mb-6">আমাদের সম্পর্কে</h1>
                
                <div class="prose prose-slate max-w-none text-[#475569] space-y-6">
                    <p class="text-lg leading-relaxed">
                        Nobo Shop-এ আপনাকে স্বাগতম। আপনার প্রতিদিনের প্রয়োজনীয় সব পণ্য এক ছাদের নিচে, সাশ্রয়ী মূল্যে এবং সেরা মানের নিশ্চয়তা নিয়ে আমরা আছি আপনার পাশে। 
                    </p>
                    
                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">আমাদের লক্ষ্য</h2>
                    <p class="leading-relaxed">
                        গ্রাহকদের সেরা মানের পণ্য সবচেয়ে দ্রুত ও সহজে পৌঁছে দেওয়াই আমাদের মূল লক্ষ্য। কেনাকাটার অভিজ্ঞতাকে আরও সহজ ও নির্ভরযোগ্য করতে আমরা নিরলস কাজ করে যাচ্ছি।
                    </p>
                    
                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">কেন আমরা?</h2>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>সেরা মানের পণ্যের নিশ্চয়তা</li>
                        <li>দ্রুততম ডেলিভারি সার্ভিস</li>
                        <li>সহজ রিটার্ন এবং রিফান্ড পলিসি</li>
                        <li>২৪/৭ কাস্টমার সাপোর্ট</li>
                    </ul>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
