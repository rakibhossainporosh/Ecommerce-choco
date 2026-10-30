<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
</script>

<template>
    <Head title="রিটার্ন পলিসি - Nobo Shop" />
    
    <div class="min-h-screen flex flex-col bg-[#FAF7F2] font-sans selection:bg-[#E86A28] selection:text-white">
        <Header />
        
        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div class="bg-white rounded-3xl shadow-sm border border-[#EAE3D6] p-8 md:p-12">
                <h1 class="text-3xl font-bold text-[#1E293B] mb-6">রিটার্ন ও রিফান্ড পলিসি</h1>
                
                <div class="prose prose-slate max-w-none text-[#475569] space-y-6">
                    <p class="text-lg leading-relaxed">
                        পণ্য হাতে পাওয়ার পর যদি কোনো ত্রুটি বা সমস্যা দেখতে পান, তবে আমাদের সহজ রিটার্ন পলিসির মাধ্যমে আপনি সহজেই পণ্যটি পরিবর্তন বা মূল্য ফেরত পেতে পারেন।
                    </p>
                    
                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">রিটার্ন করার শর্তাবলী</h2>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>পণ্যটি অবশ্যই ব্যবহার করা হয়নি এমন অবস্থায় থাকতে হবে।</li>
                        <li>পণ্যের প্যাকেজিং, ট্যাগ এবং ইনভয়েস সাথে থাকতে হবে।</li>
                        <li>পণ্য ডেলিভারি পাওয়ার ৩ দিনের মধ্যে রিটার্ন রিকোয়েস্ট করতে হবে।</li>
                        <li>পণ্যটিতে কোনো ত্রুটি থাকলে সেটি ডেলিভারি ম্যানের উপস্থিতিতেই চেক করে ফেরত দেওয়া ভালো।</li>
                    </ul>

                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">রিফান্ড প্রক্রিয়া</h2>
                    <p class="leading-relaxed">
                        পণ্য রিটার্ন পাওয়ার পর আমাদের কোয়ালিটি টিম সেটি চেক করবে। রিটার্ন গ্রহণযোগ্য হলে ৩-৫ কর্মদিবসের মধ্যে আপনার প্রদত্ত মাধ্যমে (বিকাশ/নগদ/ব্যাংক) রিফান্ড পাঠিয়ে দেওয়া হবে।
                    </p>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
