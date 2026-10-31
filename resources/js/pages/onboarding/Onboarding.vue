<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import HouseholdFields from '@/components/household/HouseholdFields.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/onboarding';
import type { CountryOption, HouseholdForm } from '@/types/household';

/**
 * Onboarding in the prototype's order (spec §2). Everything is saved at the end.
 */
const props = defineProps<{
    name: string | null;
    household: HouseholdForm;
    countries: CountryOption[];
}>();

type Step = 'name' | 'cooks' | 'people' | 'avoid' | 'plan' | 'cook';

const form = useForm<HouseholdForm & { name: string }>({
    ...props.household,
    name: props.name ?? '',
});
const household = computed<HouseholdForm>({
    get: () => form,
    set: (value) => Object.assign(form, value),
});

const steps = computed<Step[]>(() => [
    'name',
    'cooks',
    'people',
    'avoid',
    'plan',
    ...(form.who_cooks === 'self' ? [] : (['cook'] as Step[])),
]);
const index = ref(0);
const step = computed(
    () => steps.value[Math.min(index.value, steps.value.length - 1)],
);
const last = computed(() => index.value >= steps.value.length - 1);
const heading = ref<HTMLElement | null>(null);

const COPY: Record<Step, { title: string; sub: string }> = {
    name: {
        title: 'What should we call you?',
        sub: 'Your name appears on your plan and on messages to your cook.',
    },
    cooks: {
        title: 'Who does the cooking?',
        sub: 'We’ll set up step-by-step cooking, a daily menu for your cook, or both.',
    },
    people: {
        title: 'Who’s eating?',
        sub: 'Plans and shopping quantities are made for your household.',
    },
    avoid: {
        title: 'Anything to leave out?',
        sub: 'We’ll skip dishes that contain these. Always check packaged ingredients too.',
    },
    plan: {
        title: 'Pick a style for week one',
        sub: 'You can switch styles or swap any dish later.',
    },
    cook: {
        title: 'About your cook',
        sub: 'We’ll format the day’s menu for them. Nothing is sent until you tap Share.',
    },
};

/** Which step shows each field, so a server error takes the person back to it. */
const FIELD_STEP: Record<string, Step> = {
    name: 'name',
    who_cooks: 'cooks',
    people: 'people',
    nonveg_eaters: 'people',
    veg_eat_eggs: 'people',
    leave_out: 'avoid',
    plan: 'plan',
    cook_name: 'cook',
    cook_phone: 'cook',
    cook_language: 'cook',
};

async function go(to: number) {
    index.value = Math.max(0, Math.min(to, steps.value.length - 1));

    // Plan previews show Monday after diet and leave-out substitution, so fetch them for these answers.
    if (step.value === 'plan') {
        router.reload({
            only: ['countries'],
            data: {
                people: form.people,
                nonveg_eaters: form.nonveg_eaters,
                veg_eat_eggs: form.veg_eat_eggs ? 1 : 0,
                leave_out: form.leave_out,
            },
        });
    }

    await nextTick();
    heading.value?.focus();
}

function next() {
    if (step.value === 'name' && !form.name.trim()) {
        form.setError('name', 'Add your name to continue.');

        return;
    }

    form.clearErrors();

    if (!last.value) {
        void go(index.value + 1);

        return;
    }

    form.post(store.url(), {
        onError: (errors) => {
            const field = Object.keys(errors)[0]?.split('.')[0] ?? '';
            const target = steps.value.indexOf(FIELD_STEP[field] ?? 'name');
            void go(target >= 0 ? target : 0);
        },
    });
}
</script>

<template>
    <Head title="Set up your household" />

    <div
        class="mx-auto flex min-h-svh max-w-md flex-col px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-32"
    >
        <div class="flex items-center gap-3 py-2">
            <button
                type="button"
                class="size-11 rounded-full text-xl disabled:invisible"
                aria-label="Back"
                :disabled="index === 0"
                @click="go(index - 1)"
            >
                ‹
            </button>
            <div
                class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"
                role="progressbar"
                aria-label="Setup progress"
                :aria-valuemin="1"
                :aria-valuemax="steps.length"
                :aria-valuenow="index + 1"
            >
                <i
                    class="block h-full bg-primary transition-all"
                    :style="{ width: `${((index + 1) / steps.length) * 100}%` }"
                />
            </div>
            <span class="text-sm text-muted-foreground"
                >{{ index + 1 }} of {{ steps.length }}</span
            >
        </div>

        <h1
            ref="heading"
            tabindex="-1"
            class="mt-6 text-2xl font-bold outline-none"
        >
            {{ COPY[step].title }}
        </h1>
        <p class="mt-2 mb-6 text-muted-foreground">{{ COPY[step].sub }}</p>

        <form class="contents" @submit.prevent="next">
            <div v-if="step === 'name'" class="grid gap-2">
                <Label for="onb-name">Your name</Label>
                <Input
                    id="onb-name"
                    v-model="form.name"
                    autocomplete="given-name"
                    placeholder="e.g. Priya"
                    maxlength="255"
                />
                <InputError :message="form.errors.name" />
            </div>
            <HouseholdFields
                v-else
                v-model="household"
                :section="step"
                :countries="countries"
                :errors="form.errors"
            />

            <div
                class="fixed inset-x-0 bottom-0 border-t bg-background px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]"
            >
                <div class="mx-auto max-w-md">
                    <Button
                        type="submit"
                        size="lg"
                        class="w-full"
                        :disabled="form.processing"
                        data-test="onboarding-next"
                    >
                        <Spinner v-if="form.processing" />
                        {{ last ? 'Continue to free trial' : 'Continue' }}
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
