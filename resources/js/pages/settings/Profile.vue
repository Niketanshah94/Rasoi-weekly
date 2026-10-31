<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import AccountScreen from '@/components/rz/AccountScreen.vue';

defineProps<{
    country?: string | null;
}>();

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Your details" />

    <AccountScreen title="Your details">
        <Form
            v-bind="ProfileController.update.form()"
            :options="{ preserveScroll: true }"
            v-slot="{ errors, processing, recentlySuccessful }"
        >
            <label class="field" style="margin-top: 8px">
                <span class="field-label">Your name</span>
                <input
                    class="input"
                    name="name"
                    :value="user.name"
                    autocomplete="given-name"
                    required
                    maxlength="255"
                />
            </label>
            <p class="fine" style="margin-top: 6px">
                Your name appears on your plan and on messages to your cook.
            </p>
            <p v-if="errors.name" class="err">{{ errors.name }}</p>

            <section class="list">
                <h3>Signed in as</h3>
                <ul>
                    <li>
                        <div class="lrow">
                            <span
                                >{{ user.email
                                }}<small
                                    >Log in with Google or a link sent to this
                                    address.</small
                                ></span
                            >
                        </div>
                    </li>
                    <li v-if="country">
                        <div class="lrow">
                            <span
                                >{{ country
                                }}<small
                                    >Change your country in Household.</small
                                ></span
                            >
                        </div>
                    </li>
                </ul>
            </section>

            <button
                type="submit"
                class="btn primary big wide"
                style="margin-top: 22px"
                :disabled="processing"
                data-test="update-profile-button"
            >
                {{ recentlySuccessful ? 'Saved' : 'Save' }}
            </button>
        </Form>
    </AccountScreen>
</template>
