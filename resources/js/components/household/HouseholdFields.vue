<script setup lang="ts">
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import ChoiceCard from '@/components/household/ChoiceCard.vue';
import Stepper from '@/components/household/Stepper.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    DIET_MODE_LABEL,
    dietMode,
    dietModeSummary,
    WHO_COOKS,
} from '@/lib/household';
import type { CountryOption, HouseholdForm } from '@/types/household';

/**
 * The household fields, one section at a time. Onboarding shows them as steps;
 * Household settings shows them all.
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

// Keep the non-veg count within the number of people.
watch(
    () => form.value.people,
    (people) => {
        if (form.value.nonveg_eaters > people) {
            form.value.nonveg_eaters = people;
        }
    },
);

function toggleLeaveOut(value: string) {
    const list = form.value.leave_out;
    form.value.leave_out = list.includes(value)
        ? list.filter((v) => v !== value)
        : [...list, value];
}
</script>

<template>
    <div
        v-if="section === 'cooks'"
        class="grid gap-3"
        role="group"
        aria-label="Who cooks"
    >
        <ChoiceCard
            v-for="option in WHO_COOKS"
            :key="option.value"
            :title="option.name"
            :note="option.note"
            :selected="form.who_cooks === option.value"
            @select="form.who_cooks = option.value"
        />
        <InputError :message="errors.who_cooks" />
    </div>

    <div v-else-if="section === 'people'" class="grid gap-2">
        <div class="divide-y">
            <Stepper
                v-model="form.people"
                label="People at home"
                sub="Including you"
                :min="1"
                :max="12"
            />
            <Stepper
                v-model="form.nonveg_eaters"
                label="Eat chicken, mutton or fish"
                :min="0"
                :max="form.people"
            />
            <div
                v-if="form.nonveg_eaters < form.people"
                class="flex items-center justify-between gap-4 py-3"
            >
                <p class="flex flex-col">
                    <b class="font-medium">{{
                        form.nonveg_eaters
                            ? 'The others eat eggs'
                            : 'Eggs are fine'
                    }}</b>
                    <span class="text-sm text-muted-foreground"
                        >Omelettes, egg curry, egg bhurji</span
                    >
                </p>
                <button
                    type="button"
                    role="switch"
                    :aria-checked="form.veg_eat_eggs"
                    aria-label="Eggs are fine"
                    class="relative h-7 w-12 shrink-0 rounded-full transition-colors"
                    :class="
                        form.veg_eat_eggs
                            ? 'bg-primary'
                            : 'bg-muted-foreground/30'
                    "
                    @click="form.veg_eat_eggs = !form.veg_eat_eggs"
                >
                    <span
                        class="absolute top-1 left-1 size-5 rounded-full bg-background transition-transform"
                        :class="form.veg_eat_eggs ? 'translate-x-5' : ''"
                    />
                </button>
            </div>
        </div>
        <p class="rounded-lg bg-muted p-3 text-sm">
            <b>{{ DIET_MODE_LABEL[mode] }}.</b> {{ dietModeSummary(form) }}
        </p>
        <InputError :message="errors.people ?? errors.nonveg_eaters" />
    </div>

    <div v-else-if="section === 'avoid'" class="grid gap-3">
        <div class="flex flex-wrap gap-2" role="group" aria-label="Leave out">
            <button
                v-for="option in country.leaveOuts"
                :key="option.value"
                type="button"
                :aria-pressed="form.leave_out.includes(option.value)"
                class="min-h-11 rounded-full border-2 px-4 font-medium"
                :class="
                    form.leave_out.includes(option.value)
                        ? 'border-primary bg-primary/10'
                        : 'border-border'
                "
                @click="toggleLeaveOut(option.value)"
            >
                {{ option.label }}
            </button>
        </div>
        <p class="text-sm text-muted-foreground">{{ leaveOutSummary }}</p>
        <InputError
            :message="
                errors.leave_out ??
                Object.entries(errors).find(([k]) =>
                    k.startsWith('leave_out.'),
                )?.[1]
            "
        />
    </div>

    <div
        v-else-if="section === 'plan'"
        class="grid gap-3"
        role="group"
        aria-label="Plan style"
    >
        <ChoiceCard
            v-for="plan in country.plans"
            :key="plan.key"
            :title="plan.name"
            :note="plan.description"
            :selected="form.plan === plan.key"
            @select="form.plan = plan.key"
        >
            <span class="text-sm">Monday: {{ plan.monday.join(', ') }}</span>
        </ChoiceCard>
        <InputError :message="errors.plan" />
    </div>

    <div v-else-if="section === 'cook'" class="grid gap-5">
        <div class="grid gap-2">
            <Label for="cook-name">Cook’s name (optional)</Label>
            <Input
                id="cook-name"
                v-model="form.cook_name"
                placeholder="e.g. Sunita"
                maxlength="60"
            />
            <InputError :message="errors.cook_name" />
        </div>
        <div class="grid gap-2">
            <Label for="cook-phone">Cook’s WhatsApp number (optional)</Label>
            <div class="flex items-center gap-2">
                <span class="text-muted-foreground">{{
                    country.dialCode
                }}</span>
                <Input
                    id="cook-phone"
                    v-model="form.cook_phone"
                    type="tel"
                    inputmode="numeric"
                    autocomplete="off"
                    :placeholder="country.mobilePlaceholder"
                />
            </div>
            <InputError :message="errors.cook_phone" />
        </div>
        <div class="grid gap-2">
            <p id="cook-language" class="text-sm font-medium">
                Menu language for the cook
            </p>
            <div
                class="flex gap-2"
                role="group"
                aria-labelledby="cook-language"
            >
                <button
                    v-for="option in [
                        { value: 'hi', label: 'हिंदी', lang: 'hi' },
                        { value: 'en', label: 'English', lang: 'en' },
                    ] as const"
                    :key="option.value"
                    type="button"
                    :lang="option.lang"
                    :aria-pressed="form.cook_language === option.value"
                    class="min-h-11 flex-1 rounded-lg border-2 font-medium"
                    :class="
                        form.cook_language === option.value
                            ? 'border-primary bg-primary/10'
                            : 'border-border'
                    "
                    @click="form.cook_language = option.value"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>
    </div>
</template>
