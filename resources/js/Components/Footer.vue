<script setup>
import { 
    Facebook, 
    Twitter, 
    Instagram, 
    Youtube,
    Mail,
    Phone,
    MapPin
} from 'lucide-vue-next';
</script>

<template>
    <footer class="bg-white border-t border-[#EAE3D6] pt-16 pb-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-8 mb-12">
                <!-- Brand Info -->
                <div class="flex flex-col gap-4">
                    <a href="/" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-full bg-[#E86A28] flex items-center justify-center text-white shadow-sm">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xl font-bold tracking-tight text-[#1E293B] leading-tight">
                                Nobo Shop
                            </span>
                        </div>
                    </a>
                    <p class="text-[15px] text-[#64748B] leading-relaxed mt-2">
                        আপনার প্রতিদিনের কেনাকাটার নির্ভরযোগ্য প্রতিষ্ঠান। আমরা দিচ্ছি সেরা মানের পণ্যের নিশ্চয়তা।
                    </p>
                    <div class="flex items-center gap-4 mt-2">
                        <a href="#" class="w-9 h-9 rounded-full bg-[#F6F1E9] flex items-center justify-center text-[#475569] hover:bg-[#E86A28] hover:text-white transition-colors">
                            <Facebook class="w-4 h-4" />
                        </a>
                        <a href="#" class="w-9 h-9 rounded-full bg-[#F6F1E9] flex items-center justify-center text-[#475569] hover:bg-[#E86A28] hover:text-white transition-colors">
                            <Twitter class="w-4 h-4" />
                        </a>
                        <a href="#" class="w-9 h-9 rounded-full bg-[#F6F1E9] flex items-center justify-center text-[#475569] hover:bg-[#E86A28] hover:text-white transition-colors">
                            <Instagram class="w-4 h-4" />
                        </a>
                        <a href="#" class="w-9 h-9 rounded-full bg-[#F6F1E9] flex items-center justify-center text-[#475569] hover:bg-[#E86A28] hover:text-white transition-colors">
                            <Youtube class="w-4 h-4" />
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-[17px] font-bold text-[#1E293B] mb-5">প্রয়োজনীয় লিংক</h3>
                    <ul class="flex flex-col gap-3">
                        <li><a href="#" class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]">আমাদের সম্পর্কে</a></li>
                        <li><a href="#" class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]">যোগাযোগ</a></li>
                        <li><a href="#" class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]">ব্লগ</a></li>
                        <li><a href="#" class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]">অফার সমূহ</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h3 class="text-[17px] font-bold text-[#1E293B] mb-5">গ্রাহক সেবা</h3>
                    <ul class="flex flex-col gap-3">
                        <li><a href="#" class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]">রিটার্ন পলিসি</a></li>
                        <li><a href="#" class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]">প্রাইভেসি পলিসি</a></li>
                        <li><a href="#" class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]">শর্তাবলী</a></li>
                        <li><a href="#" class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]">সাধারণ জিজ্ঞাসা (FAQ)</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-[17px] font-bold text-[#1E293B] mb-5">যোগাযোগ</h3>
                    <ul class="flex flex-col gap-4">
                        <li class="flex items-start gap-3">
                            <MapPin class="w-5 h-5 text-[#E86A28] shrink-0 mt-0.5" />
                            <span class="text-[#64748B] text-[15px] leading-relaxed">
                                ১২৩, নবো শপ টাওয়ার, ধানমন্ডি,<br>ঢাকা-১২০৫, বাংলাদেশ
                            </span>
                        </li>
                        <li class="flex items-center gap-3">
                            <Phone class="w-5 h-5 text-[#E86A28] shrink-0" />
                            <span class="text-[#64748B] text-[15px]">+৮৮০ ১২৩৪ ৫৬৭৮৯০</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <Mail class="w-5 h-5 text-[#E86A28] shrink-0" />
                            <span class="text-[#64748B] text-[15px]">support@noboshop.com</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-[#EAE3D6] pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-[#64748B] text-sm">
                    © {{ new Date().getFullYear() }} Nobo Shop. সর্বস্বত্ব সংরক্ষিত।
                </p>
                <div class="flex items-center gap-3 opacity-60">
                    <div class="h-7 px-3 bg-[#F1F5F9] rounded flex items-center justify-center text-[10px] font-bold text-[#64748B] border border-[#E2E8F0]">bKash</div>
                    <div class="h-7 px-3 bg-[#F1F5F9] rounded flex items-center justify-center text-[10px] font-bold text-[#64748B] border border-[#E2E8F0]">Nagad</div>
                    <div class="h-7 px-3 bg-[#F1F5F9] rounded flex items-center justify-center text-[10px] font-bold text-[#64748B] border border-[#E2E8F0]">VISA</div>
                </div>
            </div>
        </div>
    </footer>
</template>
