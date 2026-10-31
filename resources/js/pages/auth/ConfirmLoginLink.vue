<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Rasoi',
        description: '',
    },
});

const props = defineProps<{
    email: string;
    intent: 'login' | 'signup';
    usable: boolean;
    /** The same signed address; the button posts back to it. */
    action: string;
}>();

const signingUp = props.intent === 'signup';
</script>

<template>
    <Head :title="signingUp ? 'Activate your account' : 'Log in'" />

    <div v-if="usable" class="flex flex-col gap-6 text-center">
        <h2 class="text-xl font-semibold">
            {{ signingUp ? 'Activate your account' : 'Log in to Rasoi' }}
        </h2>
        <p class="text-muted-foreground">
            {{ signingUp ? 'Create your account as' : 'You’re logging in as' }}
            <b class="text-foreground">{{ email }}</b
            >.
        </p>
        <Form :action="action" method="post" v-slot="{ processing }">
            <Button
                type="submit"
                size="lg"
                class="w-full"
                :disabled="processing"
                data-test="confirm-login-link"
            >
                <Spinner v-if="processing" />
                {{ signingUp ? 'Activate my account' : 'Log in' }}
            </Button>
        </Form>
    </div>

    <div v-else class="flex flex-col gap-6 text-center">
        <h2 class="text-xl font-semibold">This link has expired</h2>
        <p class="text-muted-foreground">
            Login links work once, for 15 minutes. Get a new one and open it on
            this device.
        </p>
        <Button as-child size="lg" class="w-full">
            <a :href="login.url({ query: { email } })">Get a new link</a>
        </Button>
    </div>
</template>
