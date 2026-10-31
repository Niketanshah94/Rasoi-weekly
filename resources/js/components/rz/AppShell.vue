<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Shell } from '@/types/app';

/**
 * The customer app frame from the prototype: header with the week and day strip, and the tab bar.
 */
const day = defineModel<number>('day', { default: 0 });

const props = defineProps<{
    shell: Shell;
    tab: 'plan' | 'dishes' | 'groceries' | 'cook';
    showDays?: boolean;
}>();

const tabs = computed(() => [
    {
        id: 'plan',
        href: '/plan',
        label: 'Plan',
        icon: [
            'M12 3a9 9 0 1 0 0 18a9 9 0 1 0 0-18z',
            'M12 7.5a4.5 4.5 0 1 0 0 9a4.5 4.5 0 1 0 0-9z',
        ],
    },
    {
        id: 'dishes',
        href: '/dishes',
        label: 'Dishes',
        icon: [
            'M3 12h18a9 9 0 0 1-18 0z',
            'M8 8c0-1.5 1-2 1-3.5M12 8c0-1.5 1-2 1-3.5M16 8c0-1.5 1-2 1-3.5',
        ],
    },
    {
        id: 'groceries',
        href: '/groceries',
        label: 'Groceries',
        icon: [
            'M5 8h14l-1.2 11.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8L5 8z',
            'M9 8V6.5a3 3 0 0 1 6 0V8',
        ],
    },
    {
        id: 'cook',
        href: '/cook',
        label: props.shell.cookTabLabel,
        icon: [
            'M7 17h10v-4a4 4 0 1 0-2.4-7.2 3.6 3.6 0 0 0-5.2 0A4 4 0 1 0 7 13v4z',
            'M7 20.5h10',
        ],
    },
]);
</script>

<template>
    <div class="rz min-h-svh">
        <header class="top">
            <div class="brand-row">
                <div class="brand"><h1>Rasoi</h1></div>
                <div class="top-right">
                    <p class="week">{{ shell.weekOf }}</p>
                    <Link href="/account" class="avatar" aria-label="Account">{{
                        shell.initial
                    }}</Link>
                </div>
            </div>
            <div
                v-if="showDays"
                class="days"
                role="group"
                aria-label="Day of the week"
            >
                <button
                    v-for="(d, i) in shell.days"
                    :key="d.short"
                    type="button"
                    class="day"
                    :aria-pressed="i === day"
                    :aria-label="`${d.full} ${d.date}${i === shell.today ? ', today' : ''}`"
                    @click="day = i"
                >
                    <span class="dname">{{ d.short }}</span>
                    <span class="dnum">{{ d.date }}</span>
                    <span
                        v-if="i === shell.today"
                        class="today"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </header>
        <main>
            <slot name="banner" />
            <section><slot /></section>
        </main>
        <nav class="nav" aria-label="Main">
            <div class="nav-inner">
                <Link
                    v-for="t in tabs"
                    :key="t.id"
                    as="button"
                    :href="t.href"
                    :data="{ day }"
                    :aria-current="t.id === tab ? 'page' : undefined"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path v-for="d in t.icon" :key="d" :d="d" />
                    </svg>
                    {{ t.label }}
                </Link>
            </div>
        </nav>
        <slot name="sheets" />
    </div>
</template>
