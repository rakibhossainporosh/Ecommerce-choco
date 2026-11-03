<script setup>
defineProps({
    count: { type: Number, default: 8 },
    tone: { type: String, default: 'grove' },
});
</script>

<template>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-2.5 sm:gap-3 lg:gap-4" aria-hidden="true">
        <div
            v-for="n in count"
            :key="n"
            class="overflow-hidden animate-pulse"
            :class="{
                'border border-[#d5e0ee] bg-white rounded-sm': tone === 'harbor',
                'border border-[#EAE3D6] bg-white rounded-2xl': tone === 'warm',
                'border border-[#e4ddd2] bg-white': tone !== 'harbor' && tone !== 'warm',
            }"
        >
            <div
                class="aspect-square"
                :class="tone === 'harbor' ? 'bg-[#e7eef8]' : tone === 'warm' ? 'bg-[#F0EAE1]' : 'bg-[#ebe4d8]'"
            />
            <div class="p-3 space-y-2">
                <div class="h-2 w-1/3 rounded-sm" :class="tone === 'harbor' ? 'bg-[#d5e0ee]' : tone === 'warm' ? 'bg-[#E2D9CD]' : 'bg-[#ddd6cb]'" />
                <div class="h-3 w-4/5 rounded-sm" :class="tone === 'harbor' ? 'bg-[#d5e0ee]' : tone === 'warm' ? 'bg-[#E2D9CD]' : 'bg-[#ddd6cb]'" />
                <div class="h-3 w-1/2 rounded-sm" :class="tone === 'harbor' ? 'bg-[#d5e0ee]' : tone === 'warm' ? 'bg-[#E2D9CD]' : 'bg-[#ddd6cb]'" />
            </div>
        </div>
    </div>
</template>
