<script setup>
import { computed } from 'vue';
import { 
    Facebook, 
    Twitter, 
    Instagram, 
    Youtube,
    Mail,
    Phone,
    MapPin
} from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const settings = computed(() => page.props.site_settings || {});

const copyrightText = computed(() => {
    const text = settings.value.footer_copyright || '© {year} Nobo Shop. সর্বস্বত্ব সংরক্ষিত।';
    return text.replace('{year}', new Date().getFullYear());
});

const isInternalLink = (url) => {
    if (!url) return false;
    return url.startsWith('/') && !url.includes('#');
};
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
                                {{ settings.site_name || 'Nobo Shop' }}
                            </span>
                        </div>
                    </a>
                    <p class="text-[15px] text-[#64748B] leading-relaxed mt-2 whitespace-pre-line">
                        {{ settings.footer_about || (settings.site_tagline ? settings.site_tagline + ' — আপনার প্রতিদিনের কেনাকাটার নির্ভরযোগ্য প্রতিষ্ঠান।' : 'আপনার প্রতিদিনের কেনাকাটার নির্ভরযোগ্য প্রতিষ্ঠান। আমরা দিচ্ছি সেরা মানের পণ্যের নিশ্চয়তা।') }}
                    </p>
                    <div class="flex items-center gap-4 mt-2">
                        <a 
                            v-if="settings.facebook_url && settings.facebook_url !== '#'" 
                            :href="settings.facebook_url" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="w-9 h-9 rounded-full bg-[#F6F1E9] flex items-center justify-center text-[#475569] hover:bg-[#E86A28] hover:text-white transition-colors" 
                            title="Facebook"
                        >
                            <Facebook class="w-4 h-4" />
                        </a>
                        <a 
                            v-if="settings.twitter_url && settings.twitter_url !== '#'" 
                            :href="settings.twitter_url" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="w-9 h-9 rounded-full bg-[#F6F1E9] flex items-center justify-center text-[#475569] hover:bg-[#E86A28] hover:text-white transition-colors" 
                            title="Twitter / X"
                        >
                            <Twitter class="w-4 h-4" />
                        </a>
                        <a 
                            v-if="settings.instagram_url && settings.instagram_url !== '#'" 
                            :href="settings.instagram_url" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="w-9 h-9 rounded-full bg-[#F6F1E9] flex items-center justify-center text-[#475569] hover:bg-[#E86A28] hover:text-white transition-colors" 
                            title="Instagram"
                        >
                            <Instagram class="w-4 h-4" />
                        </a>
                        <a 
                            v-if="settings.youtube_url && settings.youtube_url !== '#'" 
                            :href="settings.youtube_url" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="w-9 h-9 rounded-full bg-[#F6F1E9] flex items-center justify-center text-[#475569] hover:bg-[#E86A28] hover:text-white transition-colors" 
                            title="YouTube"
                        >
                            <Youtube class="w-4 h-4" />
                        </a>
                    </div>
                </div>

                <!-- Column 2: Useful Links -->
                <div>
                    <h3 class="text-[17px] font-bold text-[#1E293B] mb-5">
                        {{ settings.footer_col2_title || 'প্রয়োজনীয় লিংক' }}
                    </h3>
                    <ul class="flex flex-col gap-3">
                        <li v-for="(link, idx) in (settings.footer_col2_links || [])" :key="idx">
                            <Link 
                                v-if="isInternalLink(link.url)" 
                                :href="link.url" 
                                class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]"
                            >
                                {{ link.label }}
                            </Link>
                            <a 
                                v-else 
                                :href="link.url" 
                                class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]"
                            >
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 3: Customer Service -->
                <div>
                    <h3 class="text-[17px] font-bold text-[#1E293B] mb-5">
                        {{ settings.footer_col3_title || 'গ্রাহক সেবা' }}
                    </h3>
                    <ul class="flex flex-col gap-3">
                        <li v-for="(link, idx) in (settings.footer_col3_links || [])" :key="idx">
                            <Link 
                                v-if="isInternalLink(link.url)" 
                                :href="link.url" 
                                class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]"
                            >
                                {{ link.label }}
                            </Link>
                            <a 
                                v-else 
                                :href="link.url" 
                                class="text-[#64748B] hover:text-[#E86A28] transition-colors text-[15px]"
                            >
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 4: Contact Info -->
                <div>
                    <h3 class="text-[17px] font-bold text-[#1E293B] mb-5">
                        {{ settings.footer_col4_title || 'যোগাযোগ' }}
                    </h3>
                    <ul class="flex flex-col gap-4">
                        <li class="flex items-start gap-3">
                            <MapPin class="w-5 h-5 text-[#E86A28] shrink-0 mt-0.5" />
                            <span class="text-[#64748B] text-[15px] leading-relaxed">
                                {{ settings.contact_address || '৫২৩, নবো শপ টাওয়ার, ধানমন্ডি, ঢাকা-১২০৫, বাংলাদেশ' }}
                            </span>
                        </li>
                        <li class="flex items-center gap-3">
                            <Phone class="w-5 h-5 text-[#E86A28] shrink-0" />
                            <a :href="'tel:' + (settings.contact_phone || '+8805234567890')" class="text-[#64748B] hover:text-[#E86A28] text-[15px] transition-colors">
                                {{ settings.contact_phone || '+৮৮০ ৫২৩৪ ৫৬৭৮৯০' }}
                            </a>
                        </li>
                        <li class="flex items-center gap-3">
                            <Mail class="w-5 h-5 text-[#E86A28] shrink-0" />
                            <a :href="'mailto:' + (settings.contact_email || 'support@noboshop.com')" class="text-[#64748B] hover:text-[#E86A28] text-[15px] transition-colors">
                                {{ settings.contact_email || 'support@noboshop.com' }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Row: Copyright & Payment Badges -->
            <div class="border-t border-[#EAE3D6] pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-[#64748B] text-sm">
                    {{ copyrightText }}
                </p>
                <div v-if="settings.footer_payment_methods && settings.footer_payment_methods.length > 0" class="flex flex-wrap items-center gap-2 opacity-80">
                    <div 
                        v-for="(method, idx) in settings.footer_payment_methods" 
                        :key="idx" 
                        class="h-7 px-3 bg-[#F1F5F9] rounded flex items-center justify-center text-[10px] font-bold text-[#64748B] border border-[#E2E8F0]"
                    >
                        {{ method }}
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
