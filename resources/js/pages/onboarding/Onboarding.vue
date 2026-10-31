<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import HouseholdFields from '@/components/household/HouseholdFields.vue';
import { onBack } from '@/lib/backButton';
import { store } from '@/routes/onboarding';
import type { CountryOption, HouseholdForm } from '@/types/household';

/**
 * Onboarding in the prototype's order and look (spec §2). Everything is saved at the end.
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

// Android back goes to the previous step; on the first step it falls through to the app.
const stopBack = onBack(() => {
    if (index.value === 0) {
        return false;
    }
    void go(index.value - 1);

    return true;
});
onBeforeUnmount(stopBack);

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

    <form class="rz min-h-svh" @submit.prevent="next">
        <div class="screen">
            <div class="s-head">
                <button
                    type="button"
                    class="icon-btn"
                    aria-label="Back"
                    :style="index === 0 ? 'visibility: hidden' : undefined"
                    @click="go(index - 1)"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M15 5l-7 7 7 7" />
                    </svg>
                </button>
                <div
                    class="progress-onb"
                    role="progressbar"
                    aria-label="Setup progress"
                    :aria-valuemin="1"
                    :aria-valuemax="steps.length"
                    :aria-valuenow="index + 1"
                >
                    <i
                        :style="{
                            width: `${((index + 1) / steps.length) * 100}%`,
                        }"
                    />
                </div>
                <span class="small muted"
                    >{{ index + 1 }} of {{ steps.length }}</span
                >
            </div>

            <h1 ref="heading" tabindex="-1" class="s-title">
                {{ COPY[step].title }}
            </h1>
            <p class="s-sub">{{ COPY[step].sub }}</p>

            <template v-if="step === 'name'">
                <label class="field">
                    <span class="field-label">Your name</span>
                    <input
                        v-model="form.name"
                        class="input"
                        autocomplete="given-name"
                        placeholder="e.g. Priya"
                        maxlength="255"
                    />
                </label>
                <p v-if="form.errors.name" class="err">
                    {{ form.errors.name }}
                </p>
            </template>
            <HouseholdFields
                v-else
                v-model="household"
                :section="step"
                :countries="countries"
                :errors="form.errors"
            />
        </div>

        <div class="cta-bar">
            <div class="cta-bar-inner">
                <button
                    type="submit"
                    class="btn primary big wide"
                    :disabled="form.processing"
                    data-test="onboarding-next"
                >
                    {{ last ? 'Continue to free trial' : 'Continue' }}
                </button>
            </div>
        </div>
    </form>
</template>
