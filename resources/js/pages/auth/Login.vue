<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PasswordFields from '@/components/rz/PasswordFields.vue';
import RzScreen from '@/components/rz/RzScreen.vue';
import Status from '@/components/rz/Status.vue';
import { register } from '@/routes';
import { redirect as google } from '@/routes/google';
import { store } from '@/routes/login';
import { login as sendLoginLink } from '@/routes/login-link';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    email?: string;
    canResetPassword: boolean;
    googleEnabled: boolean;
    passwordLogin: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <RzScreen
        title="Welcome back"
        sub="Log in with Google, or get a login link by email."
        back="/"
    >
        <Status :message="status" />

        <template v-if="googleEnabled">
            <a
                :href="google.url()"
                class="btn primary big wide"
                style="margin-top: 22px"
                data-test="google-button"
                >Continue with Google</a
            >
            <div class="or">or</div>
        </template>

        <Form v-bind="sendLoginLink.form()" v-slot="{ errors, processing }">
            <label
                class="field"
                :style="googleEnabled ? 'margin-top: 0' : undefined"
            >
                <span class="field-label">Email address</span>
                <input
                    class="input"
                    type="email"
                    name="email"
                    inputmode="email"
                    autocomplete="email"
                    autocapitalize="none"
                    spellcheck="false"
                    placeholder="you@example.com"
                    :value="email"
                    required
                />
            </label>
            <p v-if="errors.email" class="err">{{ errors.email }}</p>
            <button
                type="submit"
                class="btn big wide"
                :class="{ primary: !googleEnabled }"
                style="margin-top: 14px"
                :disabled="processing"
                data-test="send-login-link"
            >
                Email me a login link
            </button>
        </Form>

        <PasswordFields
            v-if="passwordLogin"
            summary="Log in with a password instead"
        >
            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
            >
                <label class="field">
                    <span class="field-label">Email address</span>
                    <input
                        class="input"
                        type="email"
                        name="email"
                        autocomplete="email"
                        autocapitalize="none"
                        required
                    />
                </label>
                <p v-if="errors.email" class="err">{{ errors.email }}</p>
                <label class="field">
                    <span class="field-label">Password</span>
                    <input
                        class="input"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    />
                </label>
                <p v-if="errors.password" class="err">{{ errors.password }}</p>
                <input type="hidden" name="remember" value="1" />
                <button
                    type="submit"
                    class="btn primary big wide"
                    style="margin-top: 14px"
                    :disabled="processing"
                    data-test="login-button"
                >
                    Log in
                </button>
                <p v-if="canResetPassword" class="fine center">
                    <Link :href="request()" class="linkbtn"
                        >Forgot your password?</Link
                    >
                </p>
            </Form>
        </PasswordFields>

        <p class="fine center">
            New here?
            <Link :href="register()" class="linkbtn">Create an account</Link>
        </p>
        <p class="fine center">
            By continuing, you agree to our
            <Link href="/legal/terms" class="linkbtn">Terms</Link> and
            <Link href="/legal/privacy" class="linkbtn">Privacy Policy</Link>.
        </p>
    </RzScreen>
</template>
