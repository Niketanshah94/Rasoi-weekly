<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import RzScreen from '@/components/rz/RzScreen.vue';
import { login } from '@/routes';

const props = defineProps<{
    email: string;
    intent: 'login' | 'signup';
    usable: boolean;
    /** The same signed address; the button posts back to it. */
    action: string;
}>();

const signingUp = computed(() => props.intent === 'signup');
</script>

<template>
    <Head :title="signingUp ? 'Activate your account' : 'Log in'" />

    <RzScreen
        v-if="usable"
        :title="signingUp ? 'Activate your account' : 'Log in to Rasoi'"
    >
        <p class="s-sub">
            {{ signingUp ? 'Create your account as' : 'You’re logging in as' }}
            <b>{{ email }}</b
            >.
        </p>
        <Form :action="action" method="post" v-slot="{ processing }">
            <button
                type="submit"
                class="btn primary big wide"
                style="margin-top: 22px"
                :disabled="processing"
                data-test="confirm-login-link"
            >
                {{ signingUp ? 'Activate my account' : 'Log in' }}
            </button>
        </Form>
    </RzScreen>

    <RzScreen
        v-else
        title="This link has expired"
        sub="Login links work once, for 15 minutes. Get a new one and open it on this device."
    >
        <a
            :href="login.url({ query: { email } })"
            class="btn primary big wide"
            style="margin-top: 22px"
            >Get a new link</a
        >
    </RzScreen>
</template>
