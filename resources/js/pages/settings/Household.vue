<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import HouseholdFields from '@/components/household/HouseholdFields.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit, update } from '@/routes/household';
import type { CountryOption, HouseholdForm } from '@/types/household';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Household settings', href: edit() }],
    },
});

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

const SECTIONS = [
    { key: 'cooks', title: 'Who does the cooking?' },
    { key: 'people', title: 'Who’s eating?' },
    { key: 'avoid', title: 'Anything to leave out?' },
    { key: 'plan', title: 'Plan style' },
] as const;

function save() {
    form.patch(update.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Household settings" />

    <h1 class="sr-only">Household settings</h1>

    <form class="flex flex-col space-y-8" @submit.prevent="save">
        <Heading
            variant="small"
            title="Household"
            description="Who you plan for. Changes apply to this week’s plan and shopping list."
        />

        <div class="grid gap-2">
            <Label for="hh-country">Country</Label>
            <select
                id="hh-country"
                v-model="form.country"
                class="h-11 rounded-md border border-input bg-background px-3 text-base"
            >
                <option v-for="c in countries" :key="c.code" :value="c.code">
                    {{ c.name }}
                </option>
            </select>
            <p class="text-sm text-muted-foreground">
                It sets dishes, plans, prices and units.
            </p>
            <InputError :message="form.errors.country" />
        </div>

        <section
            v-for="section in SECTIONS"
            :key="section.key"
            class="grid gap-3"
        >
            <h2 class="font-semibold">{{ section.title }}</h2>
            <HouseholdFields
                v-model="household"
                :section="section.key"
                :countries="countries"
                :errors="form.errors"
            />
        </section>

        <section v-if="form.who_cooks !== 'self'" class="grid gap-3">
            <h2 class="font-semibold">About your cook</h2>
            <HouseholdFields
                v-model="household"
                section="cook"
                :countries="countries"
                :errors="form.errors"
            />
        </section>

        <div>
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="save-household"
                >Save</Button
            >
        </div>
    </form>
</template>
