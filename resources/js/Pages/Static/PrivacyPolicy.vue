<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
</script>

<template>
    <Head title="প্রাইভেসি পলিসি - Nobo Shop" />
    
    <div class="min-h-screen flex flex-col bg-[#FAF7F2] font-sans selection:bg-[#E86A28] selection:text-white">
        <Header />
        
        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div class="bg-white rounded-3xl shadow-sm border border-[#EAE3D6] p-8 md:p-12">
                <h1 class="text-3xl font-bold text-[#1E293B] mb-6">প্রাইভেসি পলিসি</h1>
                
                <div class="prose prose-slate max-w-none text-[#475569] space-y-6">
                    <p class="text-lg leading-relaxed">
                        আপনার ব্যক্তিগত তথ্যের নিরাপত্তা ও গোপনীয়তা রক্ষা করা Nobo Shop-এর অন্যতম প্রধান দায়িত্ব। 
                    </p>
                    
                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">তথ্য সংগ্রহ</h2>
                    <p class="leading-relaxed">
                        আমরা কেবল সেই তথ্যগুলোই সংগ্রহ করি যা অর্ডার ডেলিভারি এবং কাস্টমার সার্ভিস দেওয়ার জন্য প্রয়োজন। এর মধ্যে রয়েছে আপনার নাম, ফোন নম্বর, ঠিকানা এবং ইমেইল।
                    </p>

                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">তথ্য শেয়ারিং</h2>
                    <p class="leading-relaxed">
                        আমরা থার্ড-পার্টি কুরিয়ার সার্ভিসের সাথে শুধুমাত্র ডেলিভারির জন্য আপনার নাম, নম্বর এবং ঠিকানা শেয়ার করে থাকি। এর বাইরে অন্য কোনো ব্যক্তি বা প্রতিষ্ঠানের সাথে কোনো তথ্য শেয়ার করা হয় না।
                    </p>
                    
                    <h2 class="text-xl font-semibold text-[#1E293B] mt-8 mb-4">যোগাযোগ</h2>
                    <p class="leading-relaxed">
                        প্রাইভেসি পলিসি সম্পর্কিত যেকোনো প্রশ্নের জন্য আমাদের সাপোর্টে যোগাযোগ করতে পারেন।
                    </p>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
