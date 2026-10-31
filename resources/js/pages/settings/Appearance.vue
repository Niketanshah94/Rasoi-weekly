<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AccountScreen from '@/components/rz/AccountScreen.vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const OPTIONS = [
    { value: 'light', label: 'Light' },
    { value: 'dark', label: 'Dark' },
    { value: 'system', label: 'Match phone' },
] as const;
</script>

<template>
    <Head title="Appearance" />

    <AccountScreen title="Appearance">
        <p class="section-label" id="appearance-label">Colours</p>
        <div class="seg" role="group" aria-labelledby="appearance-label">
            <button
                v-for="o in OPTIONS"
                :key="o.value"
                type="button"
                :aria-pressed="appearance === o.value"
                @click="updateAppearance(o.value)"
            >
                {{ o.label }}
            </button>
        </div>
        <p class="fine">
            “Match phone” follows your phone’s light or dark setting.
        </p>
    </AccountScreen>
</template>
