<script setup lang="ts">
import { computed, watch } from 'vue';
import DietMark from '@/components/rz/DietMark.vue';
import {
    DIET_MODE_LABEL,
    dietMode,
    dietModeSummary,
    WHO_COOKS,
} from '@/lib/household';
import type { CountryOption, HouseholdForm } from '@/types/household';

/**
 * The household fields in the prototype's markup, one section at a time. Onboarding shows
 * them as steps; the Household screen shows them all.
 */
const form = defineModel<HouseholdForm>({ required: true });

const props = defineProps<{
    section: 'cooks' | 'people' | 'avoid' | 'plan' | 'cook';
    countries: CountryOption[];
    errors: Partial<Record<string, string>>;
}>();

const country = computed(
    () =>
        props.countries.find((c) => c.code === form.value.country) ??
        props.countries[0],
);
const mode = computed(() => dietMode(form.value));
const leaveOutSummary = computed(() => {
    const chosen = country.value.leaveOuts
        .filter((l) => form.value.leave_out.includes(l.value))
        .map((l) => l.label.toLowerCase());

    return chosen.length
        ? `Leaving out ${chosen.join(' and ')}.`
        : 'Nothing selected, so every dish is in.';
});
const leaveOutError = computed(
    () =>
        props.errors.leave_out ??
        Object.entries(props.errors).find(([k]) =>
            k.startsWith('leave_out.'),
        )?.[1],
);

// Keep the non-veg count within the number of people.
watch(
    () => form.value.people,
    (people) => {
        if (form.value.nonveg_eaters > people) {
            form.value.nonveg_eaters = people;
        }
    },
);

function step(field: 'people' | 'nonveg_eaters', delta: number) {
    const max = field === 'people' ? 12 : form.value.people;
    const min = field === 'people' ? 1 : 0;
    form.value[field] = Math.min(max, Math.max(min, form.value[field] + delta));
}

function toggleLeaveOut(value: string) {
    const list = form.value.leave_out;
    form.value.leave_out = list.includes(value)
        ? list.filter((v) => v !== value)
        : [...list, value];
}
</script>

<template>
    <template v-if="section === 'cooks'">
        <div class="options" role="group" aria-label="Who cooks">
            <button
                v-for="option in WHO_COOKS"
                :key="option.value"
                type="button"
                class="option"
                :aria-pressed="form.who_cooks === option.value"
                @click="form.who_cooks = option.value"
            >
                <b>{{ option.name }}</b
                ><span>{{ option.note }}</span>
            </button>
        </div>
        <p v-if="errors.who_cooks" class="err">{{ errors.who_cooks }}</p>
    </template>

    <template v-else-if="section === 'people'">
        <div class="rows">
            <div class="row-step">
                <p><b>People at home</b><span>Including you</span></p>
                <div class="stepper">
                    <button
                        type="button"
                        aria-label="Fewer: People at home"
                        :disabled="form.people <= 1"
                        @click="step('people', -1)"
                    >
                        −
                    </button>
                    <output aria-live="polite">{{ form.people }}</output>
                    <button
                        type="button"
                        aria-label="More: People at home"
                        :disabled="form.people >= 12"
                        @click="step('people', 1)"
                    >
                        +
                    </button>
                </div>
            </div>
            <div class="row-step">
                <p><b>Eat chicken, mutton or fish</b></p>
                <div class="stepper">
                    <button
                        type="button"
                        aria-label="Fewer: Eat chicken, mutton or fish"
                        :disabled="form.nonveg_eaters <= 0"
                        @click="step('nonveg_eaters', -1)"
                    >
                        −
                    </button>
                    <output aria-live="polite">{{ form.nonveg_eaters }}</output>
                    <button
                        type="button"
                        aria-label="More: Eat chicken, mutton or fish"
                        :disabled="form.nonveg_eaters >= form.people"
                        @click="step('nonveg_eaters', 1)"
                    >
                        +
                    </button>
                </div>
            </div>
            <div v-if="form.nonveg_eaters < form.people" class="row-step">
                <p>
                    <b>{{
                        form.nonveg_eaters
                            ? 'The others eat eggs'
                            : 'Eggs are fine'
                    }}</b
                    ><span>Omelettes, egg curry, egg bhurji</span>
                </p>
                <button
                    type="button"
                    class="switch"
                    role="switch"
                    :aria-checked="form.veg_eat_eggs"
                    aria-label="Eggs are fine"
                    @click="form.veg_eat_eggs = !form.veg_eat_eggs"
                >
                    <span />
                </button>
            </div>
        </div>
        <p class="note">
            <span class="diet-label"
                ><DietMark :diet="mode" />{{ DIET_MODE_LABEL[mode] }}.</span
            >{{ dietModeSummary(form) }}
        </p>
        <p v-if="errors.people || errors.nonveg_eaters" class="err">
            {{ errors.people ?? errors.nonveg_eaters }}
        </p>
    </template>

    <template v-else-if="section === 'avoid'">
        <div
            class="chips"
            role="group"
            aria-label="Leave out"
            style="margin-top: 16px"
        >
            <button
                v-for="option in country.leaveOuts"
                :key="option.value"
                type="button"
                class="chip"
                :aria-pressed="form.leave_out.includes(option.value)"
                @click="toggleLeaveOut(option.value)"
            >
                {{ option.label }}
            </button>
        </div>
        <p class="fine">{{ leaveOutSummary }}</p>
        <p v-if="leaveOutError" class="err">{{ leaveOutError }}</p>
    </template>

    <template v-else-if="section === 'plan'">
        <div class="options" role="group" aria-label="Plan style">
            <button
                v-for="plan in country.plans"
                :key="plan.key"
                type="button"
                class="option"
                :aria-pressed="form.plan === plan.key"
                @click="form.plan = plan.key"
            >
                <b>{{ plan.name }}</b
                ><span>{{ plan.description }}</span
                ><span>Monday: {{ plan.monday.join(', ') }}</span>
            </button>
        </div>
        <p v-if="errors.plan" class="err">{{ errors.plan }}</p>
    </template>

    <template v-else-if="section === 'cook'">
        <label class="field">
            <span class="field-label">Cook’s name (optional)</span>
            <input
                v-model="form.cook_name"
                class="input"
                placeholder="e.g. Sunita"
                maxlength="60"
            />
        </label>
        <p v-if="errors.cook_name" class="err">{{ errors.cook_name }}</p>
        <label class="field">
            <span class="field-label">Cook’s WhatsApp number (optional)</span>
            <span class="phone-in">
                <span>{{ country.dialCode }}</span>
                <input
                    v-model="form.cook_phone"
                    type="tel"
                    inputmode="numeric"
                    autocomplete="off"
                    :placeholder="country.mobilePlaceholder"
                />
            </span>
        </label>
        <p v-if="errors.cook_phone" class="err">{{ errors.cook_phone }}</p>
        <p id="cook-language" class="section-label">
            Menu language for the cook
        </p>
        <div class="seg" role="group" aria-labelledby="cook-language">
            <button
                type="button"
                lang="hi"
                :aria-pressed="form.cook_language === 'hi'"
                @click="form.cook_language = 'hi'"
            >
                हिंदी
            </button>
            <button
                type="button"
                :aria-pressed="form.cook_language === 'en'"
                @click="form.cook_language = 'en'"
            >
                English
            </button>
        </div>
    </template>
</template>
