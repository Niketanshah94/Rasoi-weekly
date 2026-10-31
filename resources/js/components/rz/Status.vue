<script setup lang="ts">
defineProps<{ message?: string | null }>();
</script>

<template>
    <p v-if="message" class="note" role="status" style="margin-top: 16px">
        {{ message }}
    </p>
</template>
