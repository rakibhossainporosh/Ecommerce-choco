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
    <footer class="grove-footer mt-auto bg-[#14241e] text-[#c5d9d0] border-t border-[#1a2e28]">
        <div class="max-w-[88rem] mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <div class="lg:col-span-5">
                    <a href="/" class="inline-flex items-center gap-3">
                        <img
                            v-if="settings.site_logo"
                            :src="settings.site_logo"
                            :alt="settings.site_name || 'Logo'"
                            class="h-10 w-auto max-w-[150px] object-contain"
                        />
                        <p
                            v-else
                            class="text-2xl font-semibold text-white tracking-tight"
                            style="font-family: var(--store-display)"
                        >
                            {{ settings.site_name || 'Nobo Shop' }}
                        </p>
                    </a>
                    <p class="mt-5 text-sm leading-relaxed text-[#9fb5ab] whitespace-pre-line max-w-md">
                        {{ settings.footer_about || (settings.site_tagline ? settings.site_tagline + ' — ' + t('footer.about_fallback') : t('footer.about_fallback')) }}
                    </p>
                    <div class="mt-7 flex items-center gap-3">
                        <a v-if="settings.facebook_url && settings.facebook_url !== '#'" :href="settings.facebook_url" target="_blank" rel="noopener noreferrer" class="text-[#9fb5ab] hover:text-white transition-colors"><Facebook class="w-4 h-4" /></a>
                        <a v-if="settings.twitter_url && settings.twitter_url !== '#'" :href="settings.twitter_url" target="_blank" rel="noopener noreferrer" class="text-[#9fb5ab] hover:text-white transition-colors"><Twitter class="w-4 h-4" /></a>
                        <a v-if="settings.instagram_url && settings.instagram_url !== '#'" :href="settings.instagram_url" target="_blank" rel="noopener noreferrer" class="text-[#9fb5ab] hover:text-white transition-colors"><Instagram class="w-4 h-4" /></a>
                        <a v-if="settings.youtube_url && settings.youtube_url !== '#'" :href="settings.youtube_url" target="_blank" rel="noopener noreferrer" class="text-[#9fb5ab] hover:text-white transition-colors"><Youtube class="w-4 h-4" /></a>
                    </div>
                </div>

                <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white mb-5">{{ settings.footer_col2_title || t('footer.useful_links') }}</h3>
                        <ul class="space-y-3">
                            <li v-for="(link, idx) in (settings.footer_col2_links || [])" :key="idx">
                                <Link v-if="isInternalLink(link.url)" :href="link.url" class="text-sm text-[#9fb5ab] hover:text-white">{{ link.label }}</Link>
                                <a v-else :href="link.url" class="text-sm text-[#9fb5ab] hover:text-white">{{ link.label }}</a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white mb-5">{{ settings.footer_col3_title || t('footer.customer_service') }}</h3>
                        <ul class="space-y-3">
                            <li v-for="(link, idx) in (settings.footer_col3_links || [])" :key="idx">
                                <Link v-if="isInternalLink(link.url)" :href="link.url" class="text-sm text-[#9fb5ab] hover:text-white">{{ link.label }}</Link>
                                <a v-else :href="link.url" class="text-sm text-[#9fb5ab] hover:text-white">{{ link.label }}</a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white mb-5">{{ settings.footer_col4_title || t('footer.contact') }}</h3>
                        <ul class="space-y-3 text-sm text-[#9fb5ab]">
                            <li class="flex items-start gap-2"><MapPin class="w-4 h-4 text-[#9fd6c4] mt-0.5 shrink-0" /><span>{{ settings.contact_address || t('footer.default_address') }}</span></li>
                            <li class="flex items-center gap-2"><Phone class="w-4 h-4 text-[#9fd6c4] shrink-0" /><a :href="'tel:' + (settings.contact_phone_tel || '+8805234567890')" class="hover:text-white">{{ settings.contact_phone }}</a></li>
                            <li class="flex items-center gap-2"><Mail class="w-4 h-4 text-[#9fd6c4] shrink-0" /><a :href="getGmailComposeUrl(settings.contact_email)" target="_blank" rel="noopener noreferrer" class="hover:text-white">{{ settings.contact_email || 'support@noboshop.com' }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-14 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3">
                <FooterCredit
                    text-class="text-sm text-[#7f978c] text-center sm:text-left"
                    link-class="font-medium text-[#9fd6c4] transition-colors duration-150 hover:text-white"
                />
                <div v-if="settings.footer_payment_methods?.length" class="flex flex-wrap gap-3 justify-center">
                    <span
                        v-for="(method, idx) in settings.footer_payment_methods"
                        :key="idx"
                        class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#9fb5ab]"
                    >{{ method }}</span>
                </div>
            </div>
        </div>
    </footer>
</template>
