<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { redirect } from '@/routes/google';

const props = defineProps<{
    /** Sign-up passes the chosen country; log-in passes none. */
    country?: string;
}>();
</script>

<template>
    <Button as-child size="lg" class="w-full">
        <a
            :href="
                redirect.url(
                    props.country ? { query: { country: props.country } } : {},
                )
            "
            data-test="google-button"
        >
            Continue with Google
        </a>
    </Button>
</template>
