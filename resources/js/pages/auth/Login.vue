<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import GoogleButton from '@/components/GoogleButton.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { login as sendLoginLink } from '@/routes/login-link';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Welcome back',
        description: 'Log in with Google, or get a login link by email.',
    },
});

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

    <p
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-700 dark:text-green-400"
        role="status"
    >
        {{ status }}
    </p>

    <div class="flex flex-col gap-6">
        <template v-if="googleEnabled">
            <GoogleButton />
            <p class="text-center text-sm text-muted-foreground">or</p>
        </template>

        <Form
            v-bind="sendLoginLink.form()"
            v-slot="{ errors, processing }"
            class="grid gap-4"
        >
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
                <InputError :message="errors.email" />
            </div>
            <Button
                type="submit"
                size="lg"
                variant="outline"
                :disabled="processing"
                data-test="send-login-link"
            >
                <Spinner v-if="processing" />
                Email me a login link
            </Button>
        </Form>

        <details v-if="passwordLogin" class="rounded-lg border p-4 text-sm">
            <summary class="cursor-pointer font-medium">
                Log in with a password instead
            </summary>
            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="mt-4 grid gap-4"
            >
                <div class="grid gap-2">
                    <Label for="password-email">Email address</Label>
                    <Input
                        id="password-email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        required
                    />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm"
                        >
                            Forgot your password?
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    />
                    <InputError :message="errors.password" />
                </div>
                <input type="hidden" name="remember" value="1" />
                <Button
                    type="submit"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Log in
                </Button>
            </Form>
        </details>

        <p class="text-center text-sm text-muted-foreground">
            New here?
            <TextLink :href="register()">Create an account</TextLink>
        </p>
    </div>
</template>
