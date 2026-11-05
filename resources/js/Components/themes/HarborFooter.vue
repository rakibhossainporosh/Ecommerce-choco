<script setup>
import { computed } from 'vue';
import { Facebook, Twitter, Instagram, Youtube, Mail, Phone, MapPin } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from '../../Composables/useI18n';
import FooterCredit from '../FooterCredit.vue';

const page = usePage();
const { t } = useI18n();
const settings = computed(() => page.props.site_settings || {});

const isInternalLink = (url) => Boolean(url && url.startsWith('/') && !url.includes('#'));

const getGmailComposeUrl = (email) => `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(email || 'support@noboshop.com')}`;
</script>

<template>
    <footer class="harbor-footer mt-auto bg-[#0f172a] text-[#e2e8f0]">
        <div class="border-b border-white/10">
            <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#93c5fd]">{{ settings.site_tagline || t('header.tagline_fallback') }}</p>
                    <a href="/" class="mt-2 inline-flex items-center gap-3">
                        <img
                            v-if="settings.site_logo"
                            :src="settings.site_logo"
                            :alt="settings.site_name || 'Logo'"
                            class="h-10 w-auto max-w-[150px] object-contain"
                        />
                        <h3 v-else class="text-xl font-black text-white">{{ settings.site_name || 'Nobo Shop' }}</h3>
                    </a>
                </div>
                <p class="max-w-xl text-sm text-[#cbd5e1] leading-relaxed whitespace-pre-line">
                    {{ settings.footer_about || (settings.site_tagline ? settings.site_tagline + ' — ' + t('footer.about_fallback') : t('footer.about_fallback')) }}
                </p>
            </div>
        </div>

        <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="col-span-2 md:col-span-1">
                <h3 class="text-[11px] font-black uppercase tracking-[0.16em] text-white mb-4">{{ settings.footer_col2_title || t('footer.useful_links') }}</h3>
                <ul class="space-y-2">
                    <li v-for="(link, idx) in (settings.footer_col2_links || [])" :key="idx">
                        <Link v-if="isInternalLink(link.url)" :href="link.url" class="text-sm text-[#cbd5e1] hover:text-white">{{ link.label }}</Link>
                        <a v-else :href="link.url" class="text-sm text-[#cbd5e1] hover:text-white">{{ link.label }}</a>
                    </li>
                </ul>
            </div>
            <div>
                <h3 class="text-[11px] font-black uppercase tracking-[0.16em] text-white mb-4">{{ settings.footer_col3_title || t('footer.customer_service') }}</h3>
                <ul class="space-y-2">
                    <li v-for="(link, idx) in (settings.footer_col3_links || [])" :key="idx">
                        <Link v-if="isInternalLink(link.url)" :href="link.url" class="text-sm text-[#cbd5e1] hover:text-white">{{ link.label }}</Link>
                        <a v-else :href="link.url" class="text-sm text-[#cbd5e1] hover:text-white">{{ link.label }}</a>
                    </li>
                </ul>
            </div>
            <div class="col-span-2">
                <h3 class="text-[11px] font-black uppercase tracking-[0.16em] text-white mb-4">{{ settings.footer_col4_title || t('footer.contact') }}</h3>
                <ul class="space-y-3 text-sm text-[#cbd5e1]">
                    <li class="flex items-start gap-2"><MapPin class="w-4 h-4 text-[#60a5fa] mt-0.5 shrink-0" /><span>{{ settings.contact_address || t('footer.default_address') }}</span></li>
                    <li class="flex items-center gap-2"><Phone class="w-4 h-4 text-[#60a5fa] shrink-0" /><a :href="'tel:' + (settings.contact_phone_tel || '+8805234567890')" class="hover:text-white">{{ settings.contact_phone }}</a></li>
                    <li class="flex items-center gap-2"><Mail class="w-4 h-4 text-[#60a5fa] shrink-0" /><a :href="getGmailComposeUrl(settings.contact_email)" target="_blank" rel="noopener noreferrer" class="hover:text-white">{{ settings.contact_email || 'support@noboshop.com' }}</a></li>
                </ul>
                <div class="mt-5 flex items-center gap-2">
                    <a v-if="settings.facebook_url && settings.facebook_url !== '#'" :href="settings.facebook_url" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-sm bg-white/5 hover:bg-[#1d4ed8] flex items-center justify-center"><Facebook class="w-3.5 h-3.5" /></a>
                    <a v-if="settings.twitter_url && settings.twitter_url !== '#'" :href="settings.twitter_url" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-sm bg-white/5 hover:bg-[#1d4ed8] flex items-center justify-center"><Twitter class="w-3.5 h-3.5" /></a>
                    <a v-if="settings.instagram_url && settings.instagram_url !== '#'" :href="settings.instagram_url" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-sm bg-white/5 hover:bg-[#1d4ed8] flex items-center justify-center"><Instagram class="w-3.5 h-3.5" /></a>
                    <a v-if="settings.youtube_url && settings.youtube_url !== '#'" :href="settings.youtube_url" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-sm bg-white/5 hover:bg-[#1d4ed8] flex items-center justify-center"><Youtube class="w-3.5 h-3.5" /></a>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#94a3b8]">
                <FooterCredit
                    text-class="text-center sm:text-left"
                    link-class="font-medium text-[#60a5fa] transition-colors duration-150 hover:text-[#93c5fd]"
                />
                <div v-if="settings.footer_payment_methods?.length" class="flex flex-wrap gap-1.5">
                    <span v-for="(method, idx) in settings.footer_payment_methods" :key="idx" class="px-2 py-1 border border-white/15 rounded-sm uppercase tracking-wider text-[10px] font-bold text-[#cbd5e1]">{{ method }}</span>
                </div>
            </div>
        </div>
    </footer>
</template>
