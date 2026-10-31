<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '@/components/rz/AppShell.vue';
import DietMark from '@/components/rz/DietMark.vue';
import MoodTag from '@/components/rz/MoodTag.vue';
import RecipeBody from '@/components/rz/RecipeBody.vue';
import Sheet from '@/components/rz/Sheet.vue';
import { swap as swapRoute } from '@/routes/plan';
import type { DishCard, HouseholdSummary, Recipe, Shell } from '@/types/app';
import { MEALS, plural, timeLabel } from '@/types/app';

type LibraryDish = DishCard & { countries: string[]; search: string };

const props = defineProps<{
    shell: Shell;
    day: number;
    household: HouseholdSummary;
    country: string;
    poolCount: number;
    cuisines: Record<string, string>;
    dishes: LibraryDish[];
    recipe?: Recipe | null;
}>();

const dayIndex = ref(props.day);
const query = ref('');
const meal = ref('all');
const sort = ref('rec');
const moods = ref<string[]>([]);
const cuisineFilter = ref<string[]>([]);
const time = ref(0);
const nutrition = ref<string[]>([]);
const spice = ref<number[]>([]);

const SORTS = [
    ['rec', 'Recommended'],
    ['protein', 'Most protein'],
    ['kcal', 'Fewest calories'],
    ['time', 'Quickest'],
] as const;

/** Own-country dishes first, then shared ones, then Indian dishes abroad (as the prototype ranks them). */
function rank(d: LibraryDish): number {
    if (d.countries.includes('IN')) {
        return d.countries.includes(props.country) ? 1 : 0;
    }

    return d.countries[0] === props.country
        ? 3
        : d.countries.includes(props.country)
          ? 2
          : 0;
}

const results = computed(() => {
    const q = query.value.trim().toLowerCase();
    const list = props.dishes.filter(
        (d) =>
            (meal.value === 'all' || d.meals.includes(meal.value)) &&
            (!moods.value.length || moods.value.includes(d.mood)) &&
            (!cuisineFilter.value.length ||
                cuisineFilter.value.includes(d.cuisineCode)) &&
            (!time.value || d.minutes <= time.value) &&
            (!spice.value.length || spice.value.includes(d.spice)) &&
            (!nutrition.value.includes('protein') || d.protein >= 20) &&
            (!nutrition.value.includes('light') || d.kcal < 400) &&
            (!nutrition.value.includes('fibre') || d.fibre >= 8) &&
            (!q || d.search.includes(q)),
    );

    const by: Record<string, (a: LibraryDish, b: LibraryDish) => number> = {
        protein: (a, b) => b.protein - a.protein,
        kcal: (a, b) => a.kcal - b.kcal,
        time: (a, b) => a.minutes - b.minutes,
        rec: (a, b) => rank(b) - rank(a),
    };

    return [...list].sort(by[sort.value]);
});

const activeFilters = computed(
    () =>
        moods.value.length +
        cuisineFilter.value.length +
        nutrition.value.length +
        spice.value.length +
        (time.value ? 1 : 0),
);

function clearFilters() {
    query.value = '';
    meal.value = 'all';
    moods.value = [];
    cuisineFilter.value = [];
    time.value = 0;
    nutrition.value = [];
    spice.value = [];
}

/** The list with the value added, or removed if it was there. */
function toggled<T>(list: T[], value: T): T[] {
    return list.includes(value)
        ? list.filter((v) => v !== value)
        : [...list, value];
}

const sheet = ref<'recipe' | 'filters' | null>(null);
const sheetOpen = computed({
    get: () => sheet.value !== null,
    set: (v) => !v && (sheet.value = null),
});
const recipeKey = ref<string | null>(null);
const recipe = computed(() =>
    props.recipe && props.recipe.key === recipeKey.value ? props.recipe : null,
);
const added = ref<string | null>(null);

function openRecipe(key: string) {
    recipeKey.value = key;
    added.value = null;
    sheet.value = 'recipe';
    router.reload({ only: ['recipe'], data: { recipe: key } });
}

function addTo(mealValue: string) {
    if (!recipeKey.value) {
        return;
    }
    router.post(
        swapRoute.url(),
        { day: dayIndex.value, meal: mealValue, dish: recipeKey.value },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => (added.value = mealValue),
        },
    );
}

const mealLabel = (value: string) =>
    MEALS.find((m) => m.value === value)?.label.toLowerCase() ?? value;
</script>

<template>
    <Head title="Dishes" />

    <AppShell v-model:day="dayIndex" :shell="shell" tab="dishes" show-days>
        <div class="lib-head block">
            <h2>Dishes</h2>
            <p class="muted">
                {{ poolCount }} dishes from
                {{ Object.keys(cuisines).length }} cuisines. Recipes are free
                for everyone.
            </p>
        </div>
        <div class="search-row">
            <label class="search">
                <span class="sr">Search dishes</span>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="6.5" />
                    <path d="M20 20l-4.2-4.2" />
                </svg>
                <input
                    v-model="query"
                    type="search"
                    placeholder="Search dishes or ingredients"
                    autocomplete="off"
                />
            </label>
            <button
                type="button"
                class="filter-btn"
                :aria-label="`Filters${activeFilters ? `, ${activeFilters} active` : ''}`"
                @click="sheet = 'filters'"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 6h16M7 12h10M10 18h4" /></svg
                >Filters<span
                    v-if="activeFilters"
                    class="badge"
                    aria-hidden="true"
                    >{{ activeFilters }}</span
                >
            </button>
        </div>
        <div class="chips scroll" role="group" aria-label="Meal">
            <button
                type="button"
                class="chip"
                :aria-pressed="meal === 'all'"
                @click="meal = 'all'"
            >
                All meals
            </button>
            <button
                v-for="m in MEALS"
                :key="m.value"
                type="button"
                class="chip"
                :aria-pressed="meal === m.value"
                @click="meal = m.value"
            >
                {{ m.label }}
            </button>
        </div>

        <div class="results-head">
            <p>
                <b>{{ results.length }}</b>
                {{ plural(results.length, 'dish', 'dishes') }}
                <span class="hh"
                    >For your household: {{ household.summary.toLowerCase() }}.
                    <Link href="/settings/household" class="linkbtn"
                        >Edit</Link
                    ></span
                >
            </p>
            <label class="sort">
                <span class="sr">Sort by</span>
                <select v-model="sort">
                    <option v-for="[v, l] in SORTS" :key="v" :value="v">
                        {{ l }}
                    </option>
                </select>
            </label>
        </div>
        <ul v-if="results.length" class="lib-list">
            <li v-for="d in results" :key="d.key">
                <button
                    type="button"
                    class="lib-card"
                    @click="openRecipe(d.key)"
                >
                    <span class="lib-name"
                        ><DietMark :diet="d.diet" />{{ d.name }}</span
                    >
                    <span v-if="d.nameHi" class="lib-hi" lang="hi">{{
                        d.nameHi
                    }}</span>
                    <span class="lib-meta">
                        <MoodTag :mood="d.mood" :label="d.moodLabel" /><span>{{
                            d.cuisine
                        }}</span
                        ><span>{{ timeLabel(d.minutes) }}</span
                        ><span>{{ d.kcal }} kcal</span
                        ><span>{{ d.protein }} g protein</span>
                    </span>
                </button>
            </li>
        </ul>
        <div v-else class="empty">
            <p>No dishes match these filters.</p>
            <button type="button" class="btn" @click="clearFilters">
                Clear filters
            </button>
        </div>

        <template #sheets>
            <Sheet
                v-model="sheetOpen"
                :title="sheet === 'filters' ? 'Filter dishes' : 'Recipe'"
            >
                <template v-if="sheet === 'filters'">
                    <p class="small muted" style="margin-top: 4px">
                        Your household setting ({{
                            household.summary.toLowerCase()
                        }}) always applies.
                    </p>
                    <fieldset class="fgroup">
                        <legend>Mood</legend>
                        <div class="chips">
                            <button
                                v-for="[v, l] in [
                                    ['healthy', 'Wholesome'],
                                    ['comfort', 'Comfort'],
                                    ['treat', 'Treat'],
                                ]"
                                :key="v"
                                type="button"
                                class="chip"
                                :aria-pressed="moods.includes(v)"
                                @click="moods = toggled(moods, v)"
                            >
                                {{ l }}
                            </button>
                        </div>
                    </fieldset>
                    <fieldset class="fgroup">
                        <legend>Ready in</legend>
                        <div class="chips">
                            <button
                                v-for="[v, l] in [
                                    [0, 'Any time'],
                                    [15, '15 min or less'],
                                    [30, '30 min or less'],
                                    [45, '45 min or less'],
                                ] as const"
                                :key="v"
                                type="button"
                                class="chip"
                                :aria-pressed="time === v"
                                @click="time = v"
                            >
                                {{ l }}
                            </button>
                        </div>
                    </fieldset>
                    <fieldset class="fgroup">
                        <legend>Nutrition</legend>
                        <div class="chips">
                            <button
                                v-for="[v, l] in [
                                    ['protein', 'High protein (20 g+)'],
                                    ['light', 'Under 400 kcal'],
                                    ['fibre', 'High fibre (8 g+)'],
                                ]"
                                :key="v"
                                type="button"
                                class="chip"
                                :aria-pressed="nutrition.includes(v)"
                                @click="nutrition = toggled(nutrition, v)"
                            >
                                {{ l }}
                            </button>
                        </div>
                    </fieldset>
                    <fieldset class="fgroup">
                        <legend>Spice level</legend>
                        <div class="chips">
                            <button
                                v-for="(l, i) in ['Mild', 'Medium', 'Spicy']"
                                :key="l"
                                type="button"
                                class="chip"
                                :aria-pressed="spice.includes(i)"
                                @click="spice = toggled(spice, i)"
                            >
                                {{ l }}
                            </button>
                        </div>
                    </fieldset>
                    <fieldset class="fgroup">
                        <legend>Cuisine</legend>
                        <div class="chips">
                            <button
                                v-for="(l, c) in cuisines"
                                :key="c"
                                type="button"
                                class="chip"
                                :aria-pressed="
                                    cuisineFilter.includes(String(c))
                                "
                                @click="
                                    cuisineFilter = toggled(
                                        cuisineFilter,
                                        String(c),
                                    )
                                "
                            >
                                {{ l }}
                            </button>
                        </div>
                    </fieldset>
                    <div class="sheet-foot">
                        <button type="button" class="btn" @click="clearFilters">
                            Clear all
                        </button>
                        <button
                            type="button"
                            class="btn primary"
                            @click="sheet = null"
                        >
                            Show {{ results.length }}
                            {{ plural(results.length, 'dish', 'dishes') }}
                        </button>
                    </div>
                </template>
                <template v-else>
                    <RecipeBody
                        v-if="recipe"
                        :recipe="recipe"
                        :for-who="`${household.people} ${plural(household.people, 'person', 'people')}`"
                    >
                        <template #actions>
                            <p v-if="added" class="note" role="status">
                                Added to {{ shell.days[dayIndex].full }}’s
                                {{ mealLabel(added) }}.
                            </p>
                            <div
                                v-if="shell.hasAccess"
                                class="sheet-actions stack"
                            >
                                <button
                                    v-for="(m, i) in recipe.meals"
                                    :key="m"
                                    type="button"
                                    class="btn"
                                    :class="{ primary: i === 0 }"
                                    @click="addTo(m)"
                                >
                                    Add to {{ shell.days[dayIndex].full }}’s
                                    {{ mealLabel(m) }}
                                </button>
                            </div>
                        </template>
                    </RecipeBody>
                    <p v-else class="muted" role="status">
                        Loading the recipe…
                    </p>
                </template>
            </Sheet>
        </template>
    </AppShell>
</template>
