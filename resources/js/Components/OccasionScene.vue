<script setup>
defineProps({
    kind: {
        type: String,
        required: true,
    },
    mode: {
        type: String,
        default: 'hero',
    },
});
</script>

<template>
    <svg v-if="kind === 'eid_fitr'" class="scene" :data-mode="mode" viewBox="0 0 800 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <rect class="scene-field" width="800" height="460" fill="#0c4a3c" />
        <circle cx="150" cy="70" r="3" fill="#f6e7b4" />
        <circle cx="250" cy="120" r="2" fill="#f6e7b4" />
        <circle cx="620" cy="60" r="2.5" fill="#f6e7b4" />
        <path fill="#f6e7b4" d="M560 78c-46 6-78 46-78 94 0 48 32 88 78 94-42-12-72-50-72-94s30-82 72-94z" />
        <path fill="#e7c56a" d="M250 250c40-70 90-110 150-128 8 34-8 62-36 78 28 8 48 28 52 56-36 8-78 4-112-18-8 36-36 58-72 58h-8c-18 0-28-22-14-46z" />
        <rect x="180" y="300" width="280" height="18" rx="4" fill="#f6e7b4" />
        <path fill="#d7b15a" d="M210 318v78h28v-78zm70 0v78h28v-78zm70 0v78h28v-78zm70 0v78h28v-78z" />
        <path fill="#f6e7b4" d="M318 168c18 0 28-22 18-36-16 2-30 16-30 34 4 2 8 2 12 2z" />
        <rect class="scene-floor" y="396" width="800" height="64" fill="#08382e" />
    </svg>

    <svg v-else-if="kind === 'eid_adha'" class="scene" :data-mode="mode" viewBox="0 0 800 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <rect class="scene-field" width="800" height="460" fill="#3f2a12" />
        <circle cx="120" cy="80" r="2.5" fill="#f8e7b0" />
        <circle cx="210" cy="46" r="2" fill="#f8e7b0" />
        <circle cx="690" cy="110" r="2" fill="#f8e7b0" />
        <path fill="#f8e7b0" d="M610 70c-40 8-66 42-66 82s26 74 66 82c-38-14-64-46-64-82s26-68 64-82z" />
        <path fill="#f8e7b0" d="M250 250c0-70 54-120 120-120s120 50 120 120v20H250z" />
        <rect x="230" y="270" width="280" height="16" fill="#e7d3a1" />
        <rect x="300" y="150" width="18" height="80" fill="#f8e7b0" />
        <rect x="430" y="150" width="18" height="80" fill="#f8e7b0" />
        <circle cx="309" cy="142" r="14" fill="#f8e7b0" />
        <circle cx="439" cy="142" r="14" fill="#f8e7b0" />
        <path fill="#e7d3a1" d="M470 300c8-48 36-78 74-82 6-34 28-58 56-66-4 26 6 46 22 58 22-4 38-22 42-46 14 26 8 50-8 66 26 14 40 38 34 68-34 16-74 20-112 8-8 34-36 56-70 56h-6c-26 0-38-30-32-62z" />
        <rect x="520" y="360" width="10" height="36" fill="#e7d3a1" />
        <rect x="548" y="364" width="10" height="32" fill="#e7d3a1" />
        <rect x="576" y="358" width="10" height="38" fill="#e7d3a1" />
        <rect class="scene-floor" y="396" width="800" height="64" fill="#2a1c0c" />
    </svg>

    <svg v-else-if="kind === 'durga_puja'" class="scene" :data-mode="mode" viewBox="0 0 800 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <rect class="scene-field" width="800" height="460" fill="#8f1d32" />
        <circle cx="620" cy="90" r="36" fill="#fde68a" />
        <path fill="#fde68a" d="M250 280c30-90 80-140 140-160-10 50-40 80-90 96 40 16 70 48 78 90-50 10-110 0-150-30 0 40-30 70-70 78 8-28 28-50 52-74z" />
        <path fill="#fecdd3" d="M360 250c18-40 48-60 84-68-6 24-22 38-48 46 20 8 34 22 38 42-24 6-54 2-74-14 0 20-16 34-34 38 4-14 14-24 26-34z" />
        <path fill="#fde68a" d="M180 340c20-8 28-28 20-48-18 6-28 24-24 42 2 2 2 4 4 6z" />
        <path fill="#fde68a" d="M470 330c22-6 32-28 22-48-20 8-30 26-24 44 1 2 1 3 2 4z" />
        <rect class="scene-floor" y="396" width="800" height="64" fill="#6f1426" />
    </svg>

    <svg v-else-if="kind === 'kali_puja'" class="scene" :data-mode="mode" viewBox="0 0 800 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <rect class="scene-field" width="800" height="460" fill="#312e81" />
        <circle cx="160" cy="80" r="28" fill="#fde68a" />
        <circle cx="300" cy="50" r="2" fill="#fde68a" />
        <circle cx="480" cy="70" r="2" fill="#fde68a" />
        <circle cx="640" cy="40" r="2.5" fill="#fde68a" />
        <path fill="#fb7185" d="M520 220c20-50 60-78 110-86-8 36-34 58-74 68 30 10 52 32 58 62-38 8-84 0-116-24 0 30-24 52-54 58 6-22 22-38 40-52-8-8-16-16-24-26z" />
        <path fill="#fde68a" d="M250 300c-20 20-20 48 0 64 8-16 8-32 0-48 14 6 22 20 20 36-16-4-28-18-28-36 10 2 16 6 20 12-4-10-8-18-12-28z" />
        <path fill="#fde68a" d="M340 280c-18 18-18 44 0 58 8-14 8-28 0-42 12 6 18 16 16 30-14-2-24-14-24-30 8 2 14 6 18 10-4-8-8-16-10-26z" />
        <rect class="scene-floor" y="396" width="800" height="64" fill="#1e1b4b" />
    </svg>

    <svg v-else-if="kind === 'noboborsho'" class="scene" :data-mode="mode" viewBox="0 0 800 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <rect class="scene-field" width="800" height="460" fill="#be123c" />
        <circle cx="560" cy="180" r="90" fill="#fff7ed" />
        <circle cx="560" cy="180" r="28" fill="#be123c" />
        <path fill="#fff7ed" d="M180 250c30-80 90-120 150-130 0 40-30 70-70 86 36 10 64 36 74 72-46 12-100 4-140-24-4 36-34 62-72 70 10-30 34-52 58-74z" />
        <circle cx="140" cy="90" r="16" fill="#fff7ed" />
        <circle cx="140" cy="90" r="6" fill="#be123c" />
        <rect class="scene-floor" y="396" width="800" height="64" fill="#9f1239" />
    </svg>

    <svg v-else-if="kind === 'christmas'" class="scene" :data-mode="mode" viewBox="0 0 800 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <rect class="scene-field" width="800" height="460" fill="#166534" />
        <circle cx="120" cy="60" r="3" fill="#f8fafc" />
        <circle cx="220" cy="100" r="2" fill="#f8fafc" />
        <circle cx="680" cy="70" r="3" fill="#f8fafc" />
        <circle cx="600" cy="140" r="2" fill="#f8fafc" />
        <path fill="#f8fafc" d="M390 120l70 90h-30l50 70h-36l54 78H282l54-78h-36l50-70h-30z" />
        <rect x="372" y="358" width="36" height="38" fill="#b45309" />
        <circle cx="250" cy="300" r="18" fill="#fecaca" />
        <rect x="242" y="282" width="16" height="12" fill="#f8fafc" />
        <rect class="scene-floor" y="396" width="800" height="64" fill="#14532d" />
    </svg>

    <svg v-else-if="kind === 'halloween'" class="scene" :data-mode="mode" viewBox="0 0 800 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <rect class="scene-field" width="800" height="460" fill="#3f2a14" />
        <circle cx="180" cy="90" r="34" fill="#fdba74" />
        <path fill="#1c1410" d="M520 160c20-10 48 0 58 16-16 4-28-2-36-12-8 12-22 16-36 10 6-6 10-12 14-14z" />
        <path fill="#1c1410" d="M600 120c16-8 38 0 46 14-12 2-22-2-28-10-6 10-18 12-28 8 4-4 8-8 10-12z" />
        <path fill="#fdba74" d="M430 230c-40 10-70 48-74 92 20-8 36-6 50 6 10-16 28-24 48-24 16 18 28 22 48 16 4-40-22-84-72-90z" />
        <path fill="#1c1410" d="M400 300h16l8 16 8-16h16l-12 18 8 20h-16l-8-14-8 14h-16l8-20z" />
        <rect class="scene-floor" y="396" width="800" height="64" fill="#2a1c0e" />
    </svg>

    <svg v-else class="scene" :data-mode="mode" viewBox="0 0 800 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <rect class="scene-field" width="800" height="460" fill="#0f172a" />
        <path fill="#f8e7b0" d="M180 80l8 22h24l-18 14 6 22-20-14-20 14 6-22-18-14h24z" />
        <path fill="#fde68a" d="M560 140l14 36h38l-30 22 12 36-34-22-34 22 12-36-30-22h38z" />
        <path fill="#f8fafc" d="M360 200l10 26h28l-22 16 8 26-24-16-24 16 8-26-22-16h28z" />
        <circle cx="300" cy="300" r="4" fill="#f8e7b0" />
        <circle cx="640" cy="80" r="3" fill="#f8e7b0" />
        <circle cx="120" cy="200" r="3" fill="#fde68a" />
        <rect class="scene-floor" y="396" width="800" height="64" fill="#020617" />
    </svg>
</template>

<style scoped>
.scene {
    display: block;
    width: 100%;
    height: 100%;
}
</style>
