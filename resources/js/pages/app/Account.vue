<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Sheet from '@/components/rz/Sheet.vue';
import { logout } from '@/routes';
import type { HouseholdSummary, Shell } from '@/types/app';

defineProps<{
    shell: Shell;
    account: {
        name: string;
        email: string;
        country: string;
        viaGoogle: boolean;
    };
    household: HouseholdSummary;
    planName: string;
}>();

const WHO_COOKS = {
    self: 'We cook ourselves',
    cook: 'A cook comes to help',
    both: 'Both',
} as const;
const deleting = ref(false);
</script>

<template>
    <Head title="Account" />

    <div class="rz min-h-svh">
        <header class="a-top">
            <Link href="/plan" class="icon-btn" aria-label="Back to the app">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M15 5l-7 7 7 7" />
                </svg>
            </Link>
            <h1>Account</h1>
        </header>
        <main class="acc">
            <section class="profile">
                <span class="avatar big" aria-hidden="true">{{
                    shell.initial
                }}</span>
                <div>
                    <b>{{ account.name || 'Your name' }}</b
                    ><span>{{ account.email }}</span>
                </div>
            </section>

            <section class="list">
                <h3>Household</h3>
                <ul>
                    <li>
                        <Link href="/settings/household" class="lrow"
                            ><span
                                >Country<small>{{
                                    account.country
                                }}</small></span
                            ></Link
                        >
                    </li>
                    <li>
                        <Link href="/settings/household" class="lrow"
                            ><span
                                >{{ household.summary
                                }}<small>{{
                                    household.leaveOutSummary
                                }}</small></span
                            ></Link
                        >
                    </li>
                    <li>
                        <Link href="/settings/household" class="lrow"
                            ><span
                                >{{ WHO_COOKS[household.whoCooks]
                                }}<small>{{
                                    household.whoCooks === 'self'
                                        ? 'Step-by-step cooking'
                                        : `Cook: ${household.cookName || 'name not added'}`
                                }}</small></span
                            ></Link
                        >
                    </li>
                    <li>
                        <Link href="/plan" class="lrow"
                            ><span
                                >Plan style<small>{{ planName }}</small></span
                            ></Link
                        >
                    </li>
                </ul>
            </section>

            <section class="list">
                <h3>Your details</h3>
                <ul>
                    <li>
                        <Link href="/settings/profile" class="lrow"
                            ><span
                                >Name<small>{{ account.name }}</small></span
                            ></Link
                        >
                    </li>
                    <li>
                        <Link href="/settings/appearance" class="lrow"
                            ><span
                                >Appearance<small
                                    >Light, dark or match your phone</small
                                ></span
                            ></Link
                        >
                    </li>
                </ul>
            </section>

            <section class="list">
                <h3>Help and legal</h3>
                <ul>
                    <li>
                        <Link href="/legal/terms" class="lrow"
                            ><span>Terms of service</span></Link
                        >
                    </li>
                    <li>
                        <Link href="/legal/privacy" class="lrow"
                            ><span>Privacy policy</span></Link
                        >
                    </li>
                    <li>
                        <Link href="/legal/refund" class="lrow"
                            ><span>Refunds and cancellation</span></Link
                        >
                    </li>
                </ul>
            </section>

            <section class="list">
                <ul>
                    <li>
                        <Link :href="logout()" as="button" class="lrow"
                            ><span>Log out</span></Link
                        >
                    </li>
                    <li>
                        <button
                            type="button"
                            class="lrow danger"
                            data-test="delete-user-button"
                            @click="deleting = true"
                        >
                            <span>Delete account</span>
                        </button>
                    </li>
                </ul>
            </section>
        </main>

        <Sheet v-model="deleting" title="Delete account">
            <h2 class="sheet-title" style="font-size: 24px">
                Delete your account?
            </h2>
            <ul class="perks plain">
                <li>Your household, plans and shopping lists are deleted.</li>
                <li>
                    Any subscription is cancelled and the AutoPay mandate
                    revoked.
                </li>
                <li>
                    We keep payment records only as long as tax law requires.
                </li>
            </ul>
            <p class="fine">This can’t be undone.</p>
            <Form
                v-bind="ProfileController.destroy.form()"
                v-slot="{ processing }"
            >
                <div class="sheet-actions">
                    <button type="button" class="btn" @click="deleting = false">
                        Keep account
                    </button>
                    <button
                        type="submit"
                        class="btn danger"
                        :disabled="processing"
                        data-test="confirm-delete-user-button"
                    >
                        Delete account
                    </button>
                </div>
            </Form>
        </Sheet>
    </div>
</template>
