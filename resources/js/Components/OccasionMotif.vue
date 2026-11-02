<script setup>
defineProps({
    kind: {
        type: String,
        required: true,
    },
    variant: {
        type: String,
        default: 'a',
    },
});
</script>

<template>
    <svg v-if="kind === 'eid_fitr' && variant === 'b'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 2.2 13.5 8l6 .5-4.6 3.4 1.6 5.6L12 14.4 7.5 17.5 9.1 11.9 4.5 8.5l6-.5z" />
    </svg>
    <svg v-else-if="kind === 'eid_fitr'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M15 3.2c-4.6.5-8 4.3-8 8.8s3.4 8.3 8 8.8c-4.2-1-7.2-4.7-7.2-8.8S10.8 4.2 15 3.2z" />
    </svg>

    <svg v-else-if="kind === 'eid_adha' && variant === 'b'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M8 13.2c.2-2.2 1.6-3.6 3.4-3.8.2-1.6 1.2-2.8 2.6-3.2-.2 1.2.2 2.2 1 2.8 1-.2 1.8-1 2-2.2.6 1.2.4 2.4-.4 3.2 1.2.6 1.8 1.8 1.6 3.2-1.6.8-3.4 1-5.2.4-.4 1.6-1.6 2.6-3.2 2.6H8.2C7 16.2 6.4 14.8 8 13.2z" />
        <path fill="currentColor" d="M7.2 16.2h1.1V20H7.2zm2.4.4h1.1V20H9.6zm3.2-.2h1.1V20h-1.1zm2.3.2h1.1V20h-1.1z" />
        <path fill="currentColor" d="M6.2 12.4c-.8.2-1.6.8-1.4 1.4.4-.6 1.2-.8 1.8-.6z" />
    </svg>
    <svg v-else-if="kind === 'eid_adha'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M4 14h16v6H4z" />
        <path fill="currentColor" d="M7 14c0-3.2 2.2-6 5-6s5 2.8 5 6z" />
        <path fill="currentColor" d="M5.2 9.2h1.6V14H5.2zM17.2 9.2H18.8V14h-1.6z" />
        <circle cx="6" cy="8.4" r="1.3" fill="currentColor" />
        <circle cx="18" cy="8.4" r="1.3" fill="currentColor" />
        <path fill="currentColor" d="M11.2 5.2c.8-.1 1.4-.8 1.6-1.6-.9.1-1.6.8-1.6 1.6z" />
    </svg>

    <svg v-else-if="kind === 'durga_puja' && variant === 'b'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 3c2 3 4 5 7 6-3 1-5 3-6 6 0-3-2-5-5-6 2-1 3.2-3 4-6z" />
    </svg>
    <svg v-else-if="kind === 'durga_puja'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <g fill="currentColor">
            <circle cx="12" cy="4.2" r="2.1" />
            <circle cx="12" cy="19.8" r="2.1" />
            <circle cx="4.2" cy="12" r="2.1" />
            <circle cx="19.8" cy="12" r="2.1" />
            <circle cx="6.6" cy="6.6" r="1.8" />
            <circle cx="17.4" cy="6.6" r="1.8" />
            <circle cx="6.6" cy="17.4" r="1.8" />
            <circle cx="17.4" cy="17.4" r="1.8" />
        </g>
        <circle cx="12" cy="12" r="2.4" fill="currentColor" />
    </svg>

    <svg v-else-if="kind === 'kali_puja' && variant === 'b'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="11" r="3" fill="currentColor" />
        <circle cx="6.5" cy="8" r="2.2" fill="currentColor" />
        <circle cx="17.5" cy="8" r="2.2" fill="currentColor" />
        <circle cx="8" cy="16" r="2.2" fill="currentColor" />
        <circle cx="16" cy="16" r="2.2" fill="currentColor" />
    </svg>
    <svg v-else-if="kind === 'kali_puja'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 3c1 3 .8 5.2 0 7.4 2.2-1.8 4-1.6 5-.2-2.2 3.6-4 5.4-5 5.4s-2.8-1.8-5-5.4c1-1.4 2.8-1.6 5 .2C12.8 8.2 13 6 12 3z" />
        <path fill="currentColor" d="M5 16.2c1.6 3 4 4.4 7 4.4s5.4-1.4 7-4.4c-2 1.6-4.4 2.3-7 2.3s-5-.7-7-2.3z" />
    </svg>

    <svg v-else-if="kind === 'noboborsho' && variant === 'b'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="3.2" fill="currentColor" />
        <path fill="currentColor" d="M11 2h2v4h-2zM11 18h2v4h-2zM2 11h4v2H2zM18 11h4v2h-4zM4.8 5.6l1.4-1.4 2.8 2.8-1.4 1.4zM15 16.4l1.4-1.4 2.8 2.8-1.4 1.4zM18.4 4.2 16.6 6l-1.4-1.4 1.8-1.8zM8.8 16.6 7 18.4 5.6 17l1.8-1.8z" />
    </svg>
    <svg v-else-if="kind === 'noboborsho'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="8" fill="currentColor" />
        <circle cx="12" cy="12" r="3.2" fill="#9f1239" />
    </svg>

    <svg v-else-if="kind === 'christmas' && variant === 'b'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M12 3v18M5 7.5 19 16.5M19 7.5 5 16.5" />
    </svg>
    <svg v-else-if="kind === 'christmas'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 3 16.5 11h-2.2L18 17H6l3.7-6H7.5z" />
        <path fill="currentColor" d="M10.5 17h3v3h-3z" />
    </svg>

    <svg v-else-if="kind === 'halloween' && variant === 'b'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M4 13c1.2-3.2 3-4.4 4.8-3.6.4 2 .2 3.4-.4 4.8-1.8.6-3.4.2-4.4-1.2zM20 13c-1.2-3.2-3-4.4-4.8-3.6-.4 2-.2 3.4.4 4.8 1.8.6 3.4.2 4.4-1.2z" />
        <ellipse cx="12" cy="14" rx="2.4" ry="1.8" fill="currentColor" />
    </svg>
    <svg v-else-if="kind === 'halloween'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 4.2c.2 1.6 1 2.2 1.6 2.8h-3.2C11 6.4 11.8 5.8 12 4.2z" />
        <ellipse cx="12" cy="14" rx="6" ry="5.4" fill="currentColor" />
    </svg>

    <svg v-else-if="variant === 'b'" class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 2.4 13.2 9l6.6.6L14 13.2l1.4 6.4L12 16.2 8.6 19.6 10 13.2 4.2 9.6 10.8 9z" />
    </svg>
    <svg v-else class="occasion-motif" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 1.5 13 8.2h6.8L14.2 12l1.6 6.6L12 15.2 8.2 18.6 9.8 12 4.2 8.2H11z" />
        <circle cx="12" cy="5" r="1" fill="currentColor" />
        <circle cx="19" cy="12" r="1" fill="currentColor" />
        <circle cx="12" cy="19" r="1" fill="currentColor" />
        <circle cx="5" cy="12" r="1" fill="currentColor" />
    </svg>
</template>

<style scoped>
.occasion-motif {
    display: block;
    width: 100%;
    height: 100%;
}
</style>
