<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, ShoppingBag, ArrowRight } from 'lucide-vue-next';
import Header from '../Components/Header.vue';
import Footer from '../Components/Footer.vue';

defineProps({
    order_number: {
        type: String,
        default: null
    }
});
</script>

<template>
    <Head title="Order Successful - Nobo Shop" />

    <div class="min-h-screen bg-[#FDFBF7] font-hind-siliguri text-[#1E293B] flex flex-col">
        <Header />

        <main class="flex-1 flex items-center justify-center p-4 sm:p-8">
            <div class="max-w-xl w-full bg-white rounded-3xl border border-[#EAE3D6] shadow-sm p-8 md:p-12 text-center">
                
                <div class="inline-flex items-center justify-center w-24 h-24 bg-[#ECFDF5] rounded-full mb-8 shadow-inner">
                    <CheckCircle class="w-12 h-12 text-[#10B981]" />
                </div>

                <h1 class="text-3xl md:text-4xl font-bold text-[#1E293B] mb-4">অর্ডার সফল হয়েছে!</h1>
                <p class="text-lg text-[#64748B] mb-8">
                    আমাদের শপ থেকে কেনাকাটা করার জন্য ধন্যবাদ। আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে এবং প্রসেসিং চলছে।
                </p>

                <div v-if="order_number" class="bg-[#FAF7F2] border border-[#EAE3D6] rounded-2xl p-6 mb-8 inline-block min-w-[250px]">
                    <span class="block text-sm font-semibold text-[#64748B] mb-1">অর্ডার নম্বর</span>
                    <span class="block text-2xl font-bold text-[#E86A28]">{{ order_number }}</span>
                </div>

                <div class="bg-[#F0FDF4] border border-[#BBF7D0] rounded-xl p-5 mb-10 text-sm md:text-base text-[#166534] font-medium flex items-center justify-center gap-3">
                    <ShoppingBag class="w-5 h-5 shrink-0" />
                    <span>ক্যাশ অন ডেলিভারি - পণ্য হাতে পেয়ে মূল্য পরিশোধ করবেন।</span>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link 
                        href="/" 
                        class="w-full sm:w-auto px-8 py-3.5 bg-[#E86A28] hover:bg-[#D35B1D] text-white font-bold text-base rounded-full shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2"
                    >
                        <span>আরও কেনাকাটা করুন</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                </div>

            </div>
        </main>
        
        <Footer />
    </div>
</template>
