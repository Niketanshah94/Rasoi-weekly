<script setup lang="ts">
/**
 * The temporary password sign-in, tucked under a disclosure like the prototype's secondary options.
 */
defineProps<{ summary: string }>();
</script>

<template>
    <details class="pw-option">
        <summary class="linkbtn">{{ summary }}</summary>
        <slot />
    </details>
</template>

<style scoped>
.pw-option {
    margin-top: 22px;
    border-top: 1px solid var(--line);
    padding-top: 14px;
}
.pw-option summary {
    cursor: pointer;
    min-height: 44px;
    display: flex;
    align-items: center;
}
</style>
