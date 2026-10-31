<script setup lang="ts">
defineProps<{
    title: string;
    note?: string;
    selected: boolean;
}>();

defineEmits<{ select: [] }>();
</script>

<template>
    <button
        type="button"
        :aria-pressed="selected"
        class="flex min-h-11 w-full flex-col gap-1 rounded-xl border-2 p-4 text-start transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        :class="
            selected
                ? 'border-primary bg-primary/5'
                : 'border-border hover:border-foreground/30'
        "
        @click="$emit('select')"
    >
        <b class="font-semibold">{{ title }}</b>
        <span v-if="note" class="text-sm text-muted-foreground">{{
            note
        }}</span>
        <slot />
    </button>
</template>
