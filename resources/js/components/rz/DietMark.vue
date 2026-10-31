<script setup lang="ts">
defineProps<{ diet: string }>();
</script>

<template>
    <span v-if="diet === 'mixed'" aria-hidden="true"
        ><span class="mark mark-veg" /><span class="mark mark-nonveg"
    /></span>
    <span v-else class="mark" :class="`mark-${diet}`" aria-hidden="true" />
</template>
