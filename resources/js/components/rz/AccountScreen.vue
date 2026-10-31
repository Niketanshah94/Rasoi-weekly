<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

/**
 * An Account sub-screen from the prototype: sticky header with a back button, then content.
 */
defineProps<{
    title: string;
    back?: string;
    /** Leaves room for a bottom action bar. */
    withCta?: boolean;
}>();
</script>

<template>
    <div class="rz min-h-svh">
        <header class="a-top">
            <Link :href="back ?? '/account'" class="icon-btn" aria-label="Back">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M15 5l-7 7 7 7" />
                </svg>
            </Link>
            <h1>{{ title }}</h1>
        </header>
        <main class="acc" :class="{ 'with-cta': withCta }"><slot /></main>
        <div v-if="$slots.cta" class="cta-bar">
            <div class="cta-bar-inner"><slot name="cta" /></div>
        </div>
    </div>
</template>
