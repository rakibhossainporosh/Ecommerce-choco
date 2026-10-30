<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
</script>

<template>
    <Head title="শর্তাবলী - Nobo Shop" />
    
    <div class="min-h-screen flex flex-col bg-[#FAF7F2] font-sans selection:bg-[#E86A28] selection:text-white">
        <Header />
        
        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div class="bg-white rounded-3xl shadow-sm border border-[#EAE3D6] p-8 md:p-12">
                <h1 class="text-3xl font-bold text-[#1E293B] mb-6">শর্তাবলী (Terms & Conditions)</h1>
                
                <div class="prose prose-slate max-w-none text-[#475569] space-y-6">
                    <p class="text-lg leading-relaxed">
                        আমাদের ওয়েবসাইট ব্যবহার এবং পণ্য কেনাকাটা করার মাধ্যমে আপনি নিম্নলিখিত শর্তাবলীর সাথে সম্মতি প্রকাশ করছেন।
                    </p>
                    
                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">সাধারণ শর্তাবলী</h2>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>পণ্য স্টক থাকা সাপেক্ষে অর্ডার কনফার্ম করা হয়।</li>
                        <li>যেকোনো পণ্যের মূল্য বা অফার পূর্ব ঘোষণা ছাড়াই পরিবর্তন করার অধিকার আমরা সংরক্ষণ করি।</li>
                        <li>ডেলিভারির সময় ট্রাফিক বা অন্যান্য কারণে সামান্য বিলম্ব হতে পারে।</li>
                    </ul>

                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">অর্ডার ক্যান্সেল</h2>
                    <p class="leading-relaxed">
                        সঠিক তথ্য প্রদান না করলে বা আমাদের সন্দেহজনক মনে হলে যেকোনো অর্ডার ক্যান্সেল করার অধিকার Nobo Shop সংরক্ষণ করে।
                    </p>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
