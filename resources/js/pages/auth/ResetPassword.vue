<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import RzScreen from '@/components/rz/RzScreen.vue';
import { update } from '@/routes/password';

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();
</script>

<template>
    <Head title="Reset password" />

    <RzScreen title="Choose a new password">
        <Form
            v-bind="update.form()"
            :transform="
                (data) => ({ ...data, token: props.token, email: props.email })
            "
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <label class="field">
                <span class="field-label">Email address</span>
                <input
                    class="input"
                    type="email"
                    name="email"
                    autocomplete="email"
                    :value="email"
                    readonly
                />
            </label>
            <p v-if="errors.email" class="err">{{ errors.email }}</p>
            <label class="field">
                <span class="field-label">New password</span>
                <input
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
                data-test="reset-password-button"
            >
                Save password
            </button>
        </Form>
    </RzScreen>
</template>
