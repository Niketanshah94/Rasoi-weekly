<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    page: string;
    pages: { key: string; short: string }[];
    title: string;
    sections: { heading: string; text?: string; items?: string[] }[];
}>();

function back() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
}
</script>

<template>
    <Head :title="title" />

    <div class="rz min-h-svh">
        <header class="a-top">
            <button
                type="button"
                class="icon-btn"
                aria-label="Back"
                @click="back"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M15 5l-7 7 7 7" />
                </svg>
            </button>
            <h1>{{ title }}</h1>
        </header>
        <div class="legal">
            <div class="seg" role="group" aria-label="Policy">
                <Link
                    v-for="p in pages"
                    :key="p.key"
                    :href="`/legal/${p.key}`"
                    as="button"
                    :aria-pressed="p.key === page"
                    >{{ p.short }}</Link
                >
            </div>
            <p class="proto-note">
                Draft outline. A lawyer writes the final text for each country
                before launch.
            </p>
            <template v-for="s in sections" :key="s.heading">
                <h2>{{ s.heading }}</h2>
                <p v-if="s.text">{{ s.text }}</p>
                <ul v-if="s.items">
                    <li v-for="item in s.items" :key="item">{{ item }}</li>
                </ul>
            </template>
        </div>
    </div>
</template>
