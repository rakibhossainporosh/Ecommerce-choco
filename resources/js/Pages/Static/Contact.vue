<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Mail, Phone, MapPin, CheckCircle2, Loader2, Send } from 'lucide-vue-next';
import Header from '../../Components/Header.vue';
import Footer from '../../Components/Footer.vue';
import { useI18n } from '../../Composables/useI18n';

const page = usePage();
const { t } = useI18n();
const settings = computed(() => page.props.site_settings || {});

const form = useForm({
    name: '',
    email_or_phone: '',
    message: '',
});

const isSuccess = ref(false);

const submitForm = () => {
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => {
            isSuccess.value = true;
            form.reset();
            setTimeout(() => {
                isSuccess.value = false;
            }, 6000);
        },
    });
};

const getGmailComposeUrl = (email) => {
    const targetEmail = email || 'support@noboshop.com';
    return `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(targetEmail)}`;
};
</script>

<template>
    <Head :title="t('meta.contact')" />
    
    <div class="store-page min-h-screen flex flex-col bg-[#FAF7F2] font-sans selection:bg-[#E86A28] selection:text-white">
        <Header />
        
        <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 w-full">
            <div class="bg-white rounded-3xl shadow-sm border border-[#EAE3D6] p-8 md:p-12">
                <h1 class="text-3xl font-bold text-[#1E293B] mb-8 text-center">{{ t('contact.title') }}</h1>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="space-y-8">
                        <div>
                            <h2 class="text-xl font-semibold text-[#1E293B] mb-4">{{ t('contact.talk') }}</h2>
                            <p class="text-[#64748B] text-[15px] leading-relaxed mb-6">
                                {{ t('contact.intro') }}
                            </p>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-[#FAF7F2] flex items-center justify-center text-[#E86A28] shrink-0">
                                    <MapPin class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-[#1E293B]">{{ t('contact.address') }}</h3>
                                    <p class="text-[#64748B] text-sm mt-1">
                                        {{ settings.contact_address || t('footer.default_address') }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-[#FAF7F2] flex items-center justify-center text-[#E86A28] shrink-0">
                                    <Phone class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-[#1E293B]">{{ t('contact.phone') }}</h3>
                                    <a :href="'tel:' + (settings.contact_phone_tel || '+8805234567890')" class="text-[#64748B] hover:text-[#E86A28] text-sm mt-1 inline-block transition-colors">
                                        {{ settings.contact_phone }}
                                    </a>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-[#FAF7F2] flex items-center justify-center text-[#E86A28] shrink-0">
                                    <Mail class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-[#1E293B]">{{ t('contact.email') }}</h3>
                                    <a 
                                        :href="getGmailComposeUrl(settings.contact_email)" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        :title="t('contact.compose_email')"
                                        class="text-[#64748B] hover:text-[#E86A28] text-sm mt-1 inline-block transition-colors"
                                    >
                                        {{ settings.contact_email || 'support@noboshop.com' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-[#FAF7F2] p-6 rounded-2xl border border-[#EAE3D6]">
                        <div v-if="isSuccess" class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3 text-green-700">
                            <CheckCircle2 class="w-5 h-5 text-green-600 shrink-0 mt-0.5" />
                            <div class="text-sm">
                                <div class="font-bold">{{ t('contact.thanks') }}</div>
                                <div>{{ t('contact.sent') }}</div>
                            </div>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-[#475569] mb-1">{{ t('contact.name') }} *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2.5 bg-white border border-[#E2D8C5] rounded-xl focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-none transition-all"
                                    :placeholder="t('contact.name_placeholder')"
                                />
                                <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#475569] mb-1">{{ t('contact.email_or_phone') }} *</label>
                                <input
                                    v-model="form.email_or_phone"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2.5 bg-white border border-[#E2D8C5] rounded-xl focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-none transition-all"
                                    :placeholder="t('contact.contact_placeholder')"
                                />
                                <p v-if="form.errors.email_or_phone" class="text-red-500 text-xs mt-1">{{ form.errors.email_or_phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#475569] mb-1">{{ t('contact.message') }} *</label>
                                <textarea
                                    v-model="form.message"
                                    required
                                    rows="4"
                                    class="w-full px-4 py-2.5 bg-white border border-[#E2D8C5] rounded-xl focus:border-[#E86A28] focus:ring-1 focus:ring-[#E86A28] outline-none transition-all"
                                    :placeholder="t('contact.message_placeholder')"
                                ></textarea>
                                <p v-if="form.errors.message" class="text-red-500 text-xs mt-1">{{ form.errors.message }}</p>
                            </div>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-3 bg-[#E86A28] hover:bg-[#D35B1D] text-white font-semibold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 disabled:opacity-60 cursor-pointer"
                            >
                                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                                <Send v-else class="w-4 h-4" />
                                <span>{{ form.processing ? t('contact.sending') : t('contact.send') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>
