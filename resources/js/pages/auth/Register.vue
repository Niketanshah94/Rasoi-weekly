<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PasswordFields from '@/components/rz/PasswordFields.vue';
import RzScreen from '@/components/rz/RzScreen.vue';
import Status from '@/components/rz/Status.vue';
import { countryFromTimeZone } from '@/lib/country';
import { login } from '@/routes';
import { redirect as google } from '@/routes/google';
import { signup as sendActivationLink } from '@/routes/login-link';
import { store } from '@/routes/register';

const props = defineProps<{
    countries: { code: string; name: string }[];
    status?: string;
    email?: string;
    googleEnabled: boolean;
    passwordLogin: boolean;
    passwordRules: string;
}>();

const country = ref(countryFromTimeZone(props.countries.map((c) => c.code)));
</script>

<template>
    <Head title="Create your account" />

    <RzScreen
        title="Create your account"
        sub="Start with Google or your email. No password needed."
        back="/"
    >
        <Status :message="status" />

        <label class="field">
            <span class="field-label">Country</span>
            <select v-model="country" class="input" autocomplete="country">
                <option v-for="c in countries" :key="c.code" :value="c.code">
                    {{ c.name }}
                </option>
            </select>
        </label>
        <p class="fine" style="margin-top: 6px">
            Your weekly plans, dishes and prices are set for this country.
        </p>

        <template v-if="googleEnabled">
            <a
                :href="google.url({ query: { country } })"
                class="btn primary big wide"
                style="margin-top: 22px"
                data-test="google-button"
                >Continue with Google</a
            >
            <div class="or">or</div>
        </template>

        <Form
            v-bind="sendActivationLink.form()"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="country" :value="country" />
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
            <p v-if="errors.email || errors.country" class="err">
                {{ errors.email ?? errors.country }}
            </p>
            <button
                type="submit"
                class="btn big wide"
                :class="{ primary: !googleEnabled }"
                style="margin-top: 14px"
                :disabled="processing"
                data-test="send-activation-link"
            >
                Email me an activation link
            </button>
        </Form>

        <PasswordFields
            v-if="passwordLogin"
            summary="Sign up with a password instead"
        >
            <Form
                v-bind="store.form()"
                :reset-on-success="['password', 'password_confirmation']"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="country" :value="country" />
                <label class="field">
                    <span class="field-label">Your name</span>
                    <input
                        id="name"
                        class="input"
                        name="name"
                        autocomplete="given-name"
                        required
                    />
                </label>
                <p v-if="errors.name" class="err">{{ errors.name }}</p>
                <label class="field">
                    <span class="field-label">Email address</span>
                    <input
                        id="password-email"
                        class="input"
                        type="email"
                        name="email"
                        autocomplete="email"
                        autocapitalize="none"
                        required
                    />
                </label>
                <p v-if="errors.email || errors.country" class="err">
                    {{ errors.email ?? errors.country }}
                </p>
                <label class="field">
                    <span class="field-label">Password</span>
                    <input
                        id="password"
                        class="input"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                        required
                    />
                </label>
                <p v-if="errors.password" class="err">{{ errors.password }}</p>
                <label class="field">
                    <span class="field-label">Confirm password</span>
                    <input
                        id="password_confirmation"
                        class="input"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        required
                    />
                </label>
                <button
                    type="submit"
                    class="btn primary big wide"
                    style="margin-top: 14px"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    Create account
                </button>
            </Form>
        </PasswordFields>

        <p class="fine center">
            Already have an account?
            <Link :href="login()" class="linkbtn">Log in</Link>
        </p>
        <p class="fine center">
            By continuing, you agree to our
            <Link href="/legal/terms" class="linkbtn">Terms</Link> and
            <Link href="/legal/privacy" class="linkbtn">Privacy Policy</Link>.
        </p>
    </RzScreen>
</template>
