<script setup lang="ts">
const value = defineModel<number>({ required: true });

const props = defineProps<{
    label: string;
    sub?: string;
    min: number;
    max: number;
}>();

function change(delta: number) {
    value.value = Math.min(props.max, Math.max(props.min, value.value + delta));
}
</script>

<template>
    <div class="flex items-center justify-between gap-4 py-3">
        <p class="flex flex-col">
            <b class="font-medium">{{ label }}</b>
            <span v-if="sub" class="text-sm text-muted-foreground">{{
                sub
            }}</span>
        </p>
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="size-11 rounded-full border text-xl disabled:opacity-40"
                :aria-label="`Fewer: ${label}`"
                :disabled="value <= min"
                @click="change(-1)"
            >
                −
            </button>
            <output
                class="w-6 text-center text-lg font-semibold"
                aria-live="polite"
                >{{ value }}</output
            >
            <button
                type="button"
                class="size-11 rounded-full border text-xl disabled:opacity-40"
                :aria-label="`More: ${label}`"
                :disabled="value >= max"
                @click="change(1)"
            >
                +
            </button>
        </div>
    </div>
</template>
