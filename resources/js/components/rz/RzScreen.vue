<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

/**
 * A full-screen Rasoi page from the prototype: back button, title, subtitle, content,
 * and an optional bottom action bar.
 */
defineProps<{
    title: string;
    sub?: string;
    /** Where the back button goes; none hides it. */
    back?: string;
    lang?: string;
}>();
</script>

<template>
    <div class="rz min-h-svh">
        <div class="screen">
            <div class="s-head">
                <Link
                    v-if="back"
                    :href="back"
                    class="icon-btn"
                    aria-label="Back"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M15 5l-7 7 7 7" />
                    </svg>
                </Link>
                <slot name="head" />
            </div>
            <h1 class="s-title" :lang="lang">{{ title }}</h1>
            <p v-if="sub" class="s-sub">{{ sub }}</p>
            <slot />
        </div>
        <div v-if="$slots.cta" class="cta-bar">
            <div class="cta-bar-inner"><slot name="cta" /></div>
        </div>
    </div>
</template>
