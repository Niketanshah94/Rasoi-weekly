<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import HouseholdFields from '@/components/household/HouseholdFields.vue';
import AccountScreen from '@/components/rz/AccountScreen.vue';
import { update } from '@/routes/household';
import type { CountryOption, HouseholdForm } from '@/types/household';

const props = defineProps<{
    household: HouseholdForm;
    countries: CountryOption[];
}>();

const form = useForm<HouseholdForm>({ ...props.household });
const household = computed<HouseholdForm>({
    get: () => form,
    set: (value) => Object.assign(form, value),
});
const country = computed(() =>
    props.countries.find((c) => c.code === form.country),
);
const countryChanged = computed(() => form.country !== props.household.country);

// A new country has its own plans and leave-outs: keep only what still applies.
watch(
    () => form.country,
    () => {
        const c = country.value;
        if (!c) {
            return;
        }
        form.leave_out = form.leave_out.filter((l) =>
            c.leaveOuts.some((o) => o.value === l),
        );
        if (!c.plans.some((p) => p.key === form.plan)) {
            form.plan = c.plans[0]?.key ?? null;
        }
    },
);

function save() {
    form.patch(update.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Household" />

    <form @submit.prevent="save">
        <AccountScreen title="Household" with-cta>
            <label class="field" style="margin-top: 8px">
                <span class="field-label">Country</span>
                <select v-model="form.country" class="input">
                    <option
                        v-for="c in countries"
                        :key="c.code"
                        :value="c.code"
                    >
                        {{ c.name }} ({{ c.dialCode }})
                    </option>
                </select>
            </label>
            <p class="fine" style="margin-top: 6px">
                Plans, dishes, prices and phone codes follow your country.{{
                    countryChanged
                        ? ' Your plan style changes to one for this country.'
                        : ''
                }}
            </p>
            <p v-if="form.errors.country" class="err">
                {{ form.errors.country }}
            </p>

            <p class="section-label">Who does the cooking?</p>
            <HouseholdFields
                v-model="household"
                section="cooks"
                :countries="countries"
                :errors="form.errors"
            />
            <p class="section-label">Who’s eating?</p>
            <HouseholdFields
                v-model="household"
                section="people"
                :countries="countries"
                :errors="form.errors"
            />
            <p class="section-label">Leave out</p>
            <HouseholdFields
                v-model="household"
                section="avoid"
                :countries="countries"
                :errors="form.errors"
            />
            <p class="section-label">Plan style</p>
            <HouseholdFields
                v-model="household"
                section="plan"
                :countries="countries"
                :errors="form.errors"
            />
            <template v-if="form.who_cooks !== 'self'">
                <p class="section-label">Your cook</p>
                <HouseholdFields
                    v-model="household"
                    section="cook"
                    :countries="countries"
                    :errors="form.errors"
                />
            </template>
            <p v-if="form.recentlySuccessful" class="note" role="status">
                Saved. This week’s plan and shopping list are updated.
            </p>

            <template #cta>
                <button
                    type="submit"
                    class="btn primary big wide"
                    :disabled="form.processing"
                    data-test="save-household"
                >
                    Save changes
                </button>
            </template>
        </AccountScreen>
    </form>
</template>
