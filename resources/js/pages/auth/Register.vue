<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import GoogleButton from '@/components/GoogleButton.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { countryFromTimeZone } from '@/lib/country';
import { login } from '@/routes';
import { signup as sendActivationLink } from '@/routes/login-link';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Create your account',
        description: 'Start with Google or your email. No password needed.',
    },
});

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

    <p
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-700 dark:text-green-400"
        role="status"
    >
        {{ status }}
    </p>

    <div class="flex flex-col gap-6">
        <div class="grid gap-2">
            <Label for="country">Country</Label>
            <select
                id="country"
                v-model="country"
                autocomplete="country"
                class="h-11 rounded-md border border-input bg-background px-3 text-base"
            >
                <option v-for="c in countries" :key="c.code" :value="c.code">
                    {{ c.name }}
                </option>
            </select>
            <p class="text-sm text-muted-foreground">
                Your weekly plans, dishes and prices are set for this country.
            </p>
        </div>

        <template v-if="googleEnabled">
            <GoogleButton :country="country" />
            <p class="text-center text-sm text-muted-foreground">or</p>
        </template>

        <Form
            v-bind="sendActivationLink.form()"
            v-slot="{ errors, processing }"
            class="grid gap-4"
        >
            <input type="hidden" name="country" :value="country" />
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    inputmode="email"
                    autocomplete="email"
                    autocapitalize="none"
                    spellcheck="false"
                    placeholder="you@example.com"
                    :default-value="email"
                    required
                />
                <InputError :message="errors.email ?? errors.country" />
            </div>
            <Button
                type="submit"
                size="lg"
                variant="outline"
                :disabled="processing"
                data-test="send-activation-link"
            >
                <Spinner v-if="processing" />
                Email me an activation link
            </Button>
        </Form>

        <details v-if="passwordLogin" class="rounded-lg border p-4 text-sm">
            <summary class="cursor-pointer font-medium">
                Sign up with a password instead
            </summary>
            <Form
                v-bind="store.form()"
                :reset-on-success="['password', 'password_confirmation']"
                v-slot="{ errors, processing }"
                class="mt-4 grid gap-4"
            >
                <input type="hidden" name="country" :value="country" />
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" autocomplete="name" required />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="password-email">Email address</Label>
                    <Input
                        id="password-email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        required
                    />
                    <InputError :message="errors.email ?? errors.country" />
                </div>
                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                        required
                    />
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        required
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>
                <Button
                    type="submit"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
            </Form>
        </details>

        <p class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink :href="login()">Log in</TextLink>
        </p>
    </div>
</template>
