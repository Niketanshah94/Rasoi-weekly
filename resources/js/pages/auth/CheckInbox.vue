<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import RzScreen from '@/components/rz/RzScreen.vue';
import { login, register } from '@/routes';
import { login as sendLoginLink, signup } from '@/routes/login-link';

const props = defineProps<{
    email: string;
    intent: 'login' | 'signup';
    country?: string | null;
    minutes: number;
}>();

const signingUp = computed(() => props.intent === 'signup');
</script>

<template>
    <Head title="Check your inbox" />

    <RzScreen
        title="Check your inbox"
        :back="signingUp ? register.url() : login.url()"
    >
        <p class="s-sub">
            We sent {{ signingUp ? 'an activation link' : 'a login link' }} to
            <b>{{ email }}</b
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
            <button
                type="submit"
                class="btn big wide"
                style="margin-top: 22px"
                :disabled="processing"
                data-test="resend-link"
            >
                {{ wasSuccessful ? 'Sent again' : 'Send it again' }}
            </button>
        </Form>
        <p class="fine center">
            Wrong address?
            <Link :href="signingUp ? register() : login()" class="linkbtn"
                >Use a different email</Link
            >
        </p>
    </RzScreen>
</template>
