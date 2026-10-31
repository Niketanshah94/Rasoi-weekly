<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '@/components/rz/AppShell.vue';
import DietMark from '@/components/rz/DietMark.vue';
import MoodTag from '@/components/rz/MoodTag.vue';
import Plate from '@/components/rz/Plate.vue';
import RecipeBody from '@/components/rz/RecipeBody.vue';
import Sheet from '@/components/rz/Sheet.vue';
import {
    cooked as cookedRoute,
    style as styleRoute,
    swap as swapRoute,
} from '@/routes/plan';
import type {
    DishCard,
    HouseholdSummary,
    PlanDay,
    PlanMeal,
    Recipe,
    Shell,
} from '@/types/app';
import { MOOD_COLOR, plural, timeLabel } from '@/types/app';

const props = defineProps<{
    shell: Shell;
    day: number;
    household: HouseholdSummary;
    plans: { key: string; name: string; description: string }[];
    plan: string;
    week: PlanDay[];
    healthyDays: number;
    swapOptions?: DishCard[];
    recipe?: Recipe | null;
}>();

const dayIndex = ref(props.day);
const today = computed(() => props.week[dayIndex.value]);

const sheet = ref<'detail' | 'swap' | null>(null);
const sheetOpen = computed({
    get: () => sheet.value !== null,
    set: (v) => !v && (sheet.value = null),
});
const meal = ref<PlanMeal | null>(null);
const recipeKey = ref<string | null>(null);
const swapFilter = ref('all');

const SWAP_FILTERS = [
    ['all', 'All'],
    ['healthy', 'Wholesome'],
    ['comfort', 'Comfort'],
    ['treat', 'Treat'],
    ['quick', '20 min or less'],
    ['protein', 'High protein'],
] as const;

const swapList = computed(() =>
    (props.swapOptions ?? []).filter((d) => {
        switch (swapFilter.value) {
            case 'all':
                return true;
            case 'quick':
                return d.minutes <= 20;
            case 'protein':
                return d.protein >= 20;
            default:
                return d.mood === swapFilter.value;
        }
    }),
);

const recipe = computed(() =>
    props.recipe && props.recipe.key === recipeKey.value ? props.recipe : null,
);
const forWho = computed(() => {
    if (!recipe.value || !meal.value) {
        return '';
    }
    const n = recipe.value.servings;
    const isPair = meal.value.pair?.key === recipe.value.key;

    return props.household.isMixed && recipe.value.diet === 'nonveg' && !isPair
        ? `${n} non-veg ${plural(n, 'eater', 'eaters')}`
        : `${n} ${plural(n, 'person', 'people')}`;
});
const currentMeal = computed(
    () =>
        today.value.meals.find((m) => m.meal === meal.value?.meal) ??
        meal.value,
);

const visit = { preserveScroll: true, preserveState: true } as const;

function openMeal(m: PlanMeal, key?: string) {
    meal.value = m;
    recipeKey.value = key ?? m.dish.key;
    sheet.value = 'detail';
    router.reload({
        only: ['recipe'],
        data: { recipe: recipeKey.value, meal: m.meal, day: dayIndex.value },
    });
}

function openSwap() {
    swapFilter.value = 'all';
    sheet.value = 'swap';
    router.reload({
        only: ['swapOptions'],
        data: { swap_meal: meal.value?.meal, day: dayIndex.value },
    });
}

function swapTo(dish: string, mealValue = meal.value?.meal) {
    router.post(
        swapRoute.url(),
        { day: dayIndex.value, meal: mealValue, dish },
        { ...visit, onSuccess: () => (sheet.value = null) },
    );
}

function toggleCooked() {
    if (!currentMeal.value) {
        return;
    }
    router.post(
        cookedRoute.url(),
        {
            day: dayIndex.value,
            meal: currentMeal.value.meal,
            cooked: !currentMeal.value.cooked,
        },
        { ...visit, onSuccess: () => (sheet.value = null) },
    );
}

function chooseStyle(key: string) {
    if (key !== props.plan) {
        router.put(styleRoute.url(), { plan: key }, visit);
    }
}
</script>

<template>
    <Head title="Plan" />

    <AppShell v-model:day="dayIndex" :shell="shell" tab="plan" show-days>
        <div class="block">
            <div class="hh-card">
                <div>
                    <b
                        ><DietMark :diet="household.dietMode" />{{
                            household.summary
                        }}</b
                    >
                    <span class="sub">{{ household.leaveOutSummary }}</span>
                </div>
                <Link href="/settings/household" class="btn small">Edit</Link>
            </div>
        </div>

        <div class="block">
            <p class="block-title">Weekly plan</p>
            <div class="plans" role="group" aria-label="Weekly plan">
                <button
                    v-for="p in plans"
                    :key="p.key"
                    type="button"
                    class="plan"
                    :aria-pressed="p.key === plan"
                    @click="chooseStyle(p.key)"
                >
                    <b>{{ p.name }}</b
                    ><span>{{ p.description }}</span>
                </button>
            </div>
        </div>

        <div class="block">
            <Plate
                :day="today"
                :day-index="dayIndex"
                :week="week"
                :shell="shell"
                :healthy-days="healthyDays"
                :is-mixed="household.isMixed"
                @select-day="dayIndex = $event"
                @fix="today.fix && swapTo(today.fix.with, today.fix.meal)"
            />
        </div>

        <div class="block">
            <ol class="meals">
                <li v-for="m in today.meals" :key="m.meal">
                    <button
                        type="button"
                        class="meal"
                        :data-cooked="m.cooked"
                        :aria-label="`${m.label}: ${m.dish.name}${m.pair ? ', veg option ' + m.pair.name : ''}, ${m.dish.kcal} kcal${m.cooked ? ', cooked' : ''}. Open details`"
                        @click="openMeal(m)"
                    >
                        <span class="time">{{ m.time }}</span>
                        <span
                            class="dot"
                            :style="{ background: MOOD_COLOR[m.dish.mood] }"
                        />
                        <span class="card">
                            <span class="slot">{{ m.label }}</span>
                            <span class="dish"
                                ><DietMark :diet="m.dish.diet" />{{
                                    m.dish.name
                                }}</span
                            >
                            <span v-if="m.pair" class="pair"
                                ><DietMark :diet="m.pair.diet" />Veg:
                                {{ m.pair.name }}</span
                            >
                            <span class="meta">
                                <MoodTag
                                    :mood="m.dish.mood"
                                    :label="m.dish.moodLabel"
                                />
                                <span
                                    >{{ timeLabel(m.dish.minutes) }},
                                    {{ m.dish.kcal }} kcal,
                                    {{ m.dish.protein }} g protein</span
                                >
                            </span>
                        </span>
                        <svg
                            v-if="m.cooked"
                            class="check"
                            viewBox="0 0 28 28"
                            aria-hidden="true"
                        >
                            <circle
                                cx="14"
                                cy="14"
                                r="12"
                                style="fill: var(--leaf)"
                            />
                            <path
                                d="M8.5 14.5l3.5 3.5 7-8"
                                fill="none"
                                style="stroke: var(--leaf-ink)"
                                stroke-width="2.4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <svg
                            v-else
                            class="check"
                            viewBox="0 0 28 28"
                            aria-hidden="true"
                        >
                            <circle
                                cx="14"
                                cy="14"
                                r="11"
                                fill="none"
                                style="stroke: var(--line)"
                                stroke-width="2"
                            />
                        </svg>
                    </button>
                </li>
            </ol>
        </div>

        <template #sheets>
            <Sheet
                v-model="sheetOpen"
                :title="
                    sheet === 'swap'
                        ? `Swap ${shell.days[dayIndex].full}’s ${currentMeal?.label.toLowerCase()}`
                        : `${shell.days[dayIndex].full}, ${currentMeal?.label.toLowerCase()} at ${currentMeal?.time}`
                "
                :back="sheet === 'swap'"
                @back="sheet = 'detail'"
            >
                <template v-if="sheet === 'detail'">
                    <RecipeBody
                        v-if="recipe"
                        :recipe="recipe"
                        :for-who="forWho"
                    >
                        <template #extra>
                            <p
                                v-if="
                                    currentMeal?.pair &&
                                    recipeKey === currentMeal.dish.key
                                "
                                class="note"
                            >
                                <span class="diet-label"
                                    ><DietMark
                                        :diet="currentMeal.pair.diet"
                                    />For the {{ household.vegEaters }} veg
                                    {{
                                        plural(
                                            household.vegEaters,
                                            'eater',
                                            'eaters',
                                        )
                                    }}:</span
                                >{{ currentMeal.pair.name }}.
                                <button
                                    type="button"
                                    class="linkbtn"
                                    @click="
                                        openMeal(
                                            currentMeal,
                                            currentMeal.pair.key,
                                        )
                                    "
                                >
                                    See recipe
                                </button>
                            </p>
                            <p v-if="!recipe.avoidsLeaveOuts" class="warn">
                                This dish has something you’re leaving out, and
                                there’s no other option for this meal yet. Tap
                                Swap dish to choose one.
                            </p>
                        </template>
                        <template #actions>
                            <div
                                v-if="recipeKey === currentMeal?.dish.key"
                                class="sheet-actions"
                            >
                                <button
                                    type="button"
                                    class="btn primary"
                                    data-test="toggle-cooked"
                                    @click="toggleCooked"
                                >
                                    {{
                                        currentMeal?.cooked
                                            ? 'Mark as not cooked'
                                            : 'Mark as cooked'
                                    }}
                                </button>
                                <button
                                    type="button"
                                    class="btn"
                                    data-test="open-swap"
                                    @click="openSwap"
                                >
                                    Swap dish
                                </button>
                            </div>
                        </template>
                    </RecipeBody>
                    <p v-else class="muted" role="status">
                        Loading the recipe…
                    </p>
                </template>

                <template v-else-if="sheet === 'swap'">
                    <div
                        class="chips scroll"
                        role="group"
                        aria-label="Show"
                        style="
                            margin-left: -20px;
                            margin-right: -20px;
                            padding-left: 20px;
                            padding-right: 20px;
                        "
                    >
                        <button
                            v-for="[v, l] in SWAP_FILTERS"
                            :key="v"
                            type="button"
                            class="chip"
                            :aria-pressed="swapFilter === v"
                            @click="swapFilter = v"
                        >
                            {{ l }}
                        </button>
                    </div>
                    <p class="small muted" style="margin-top: 10px">
                        {{ swapList.length }}
                        {{ plural(swapList.length, 'option', 'options') }} for
                        {{ household.summary.toLowerCase() }}
                    </p>
                    <ul v-if="swapList.length" class="swap">
                        <li v-for="o in swapList" :key="o.key">
                            <button
                                type="button"
                                class="swap-opt"
                                :aria-current="
                                    o.key === currentMeal?.dish.key
                                        ? 'true'
                                        : undefined
                                "
                                @click="
                                    o.key === currentMeal?.dish.key
                                        ? (sheet = 'detail')
                                        : swapTo(o.key)
                                "
                            >
                                <span class="swap-name"
                                    ><DietMark :diet="o.diet" />{{ o.name
                                    }}{{
                                        o.key === currentMeal?.dish.key
                                            ? ' (current)'
                                            : ''
                                    }}</span
                                >
                                <span class="swap-meta">
                                    <MoodTag
                                        :mood="o.mood"
                                        :label="o.moodLabel"
                                    />
                                    <span
                                        >{{ o.cuisine }},
                                        {{ timeLabel(o.minutes) }},
                                        {{ o.kcal }} kcal, {{ o.protein }} g
                                        protein</span
                                    >
                                </span>
                            </button>
                        </li>
                    </ul>
                    <div v-else-if="swapOptions" class="empty">
                        <p>Nothing here for this meal. Try another filter.</p>
                    </div>
                    <p v-else class="muted" role="status">Loading options…</p>
                </template>
            </Sheet>
        </template>
    </AppShell>
</template>
