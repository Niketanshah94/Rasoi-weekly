<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import RzScreen from '@/components/rz/RzScreen.vue';
import Status from '@/components/rz/Status.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{ status?: string }>();
</script>

<template>
    <Head title="Forgot password" />

    <RzScreen
        title="Forgot your password?"
        sub="Enter your email and we’ll send a link to choose a new one."
        :back="login.url()"
    >
        <Status :message="status" />
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <label class="field">
                <span class="field-label">Email address</span>
                <input
                    class="input"
                    type="email"
                    name="email"
                    autocomplete="email"
                    autocapitalize="none"
                    placeholder="you@example.com"
                    required
                />
            </label>
            <p v-if="errors.email" class="err">{{ errors.email }}</p>
            <button
                type="submit"
                class="btn primary big wide"
                style="margin-top: 14px"
                :disabled="processing"
                data-test="email-password-reset-link-button"
            >
                Email me a reset link
            </button>
        </Form>
        <p class="fine center">
            Remembered it? <Link :href="login()" class="linkbtn">Log in</Link>
        </p>
    </RzScreen>
</template>
