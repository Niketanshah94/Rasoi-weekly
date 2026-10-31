<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { login, register } from '@/routes';
import { login as sendLoginLink, signup } from '@/routes/login-link';

defineOptions({
    layout: {
        title: 'Check your inbox',
        description: '',
    },
});

const props = defineProps<{
    email: string;
    intent: 'login' | 'signup';
    country?: string | null;
    minutes: number;
}>();

const signingUp = props.intent === 'signup';
</script>

<template>
    <Head title="Check your inbox" />

    <div class="flex flex-col gap-6 text-center">
        <p class="text-muted-foreground">
            We sent {{ signingUp ? 'an activation link' : 'a login link' }} to
            <b class="text-foreground">{{ email }}</b
            >. Open it on this device to
            {{ signingUp ? 'activate your account' : 'log in' }}. The link works
            for {{ minutes }} minutes.
        </p>

        <Form
            v-bind="signingUp ? signup.form() : sendLoginLink.form()"
            v-slot="{ processing, wasSuccessful }"
        >
            <input type="hidden" name="email" :value="email" />
            <input
                v-if="signingUp"
                type="hidden"
                name="country"
                :value="country"
            />
            <Button
                type="submit"
                variant="outline"
                :disabled="processing"
                data-test="resend-link"
            >
                <Spinner v-if="processing" />
                {{ wasSuccessful ? 'Sent again' : 'Send it again' }}
            </Button>
        </Form>

        <p class="text-sm text-muted-foreground">
            Wrong address?
            <TextLink :href="signingUp ? register() : login()">
                Use a different email
            </TextLink>
        </p>
    </div>
</template>
