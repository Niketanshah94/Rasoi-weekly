<script setup lang="ts">
defineProps<{ mood: string; label: string }>();
</script>

<template>
    <span class="tag" :class="`tag-${mood}`"><i />{{ label }}</span>
</template>
