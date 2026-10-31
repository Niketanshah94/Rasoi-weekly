<script setup lang="ts">
import DietMark from '@/components/rz/DietMark.vue';
import MoodTag from '@/components/rz/MoodTag.vue';
import type { Recipe } from '@/types/app';
import { plural } from '@/types/app';

/**
 * The recipe sheet's body, shared by the Plan and Dishes tabs (prototype detailHTML).
 */
defineProps<{
    recipe: Recipe;
    /** "4 people" or "2 non-veg eaters". */
    forWho: string;
}>();

const SPICE = ['Mild', 'Medium', 'Spicy'];
const EFFORT = ['Easy', 'Medium', 'Involved'];
const DIET = { veg: 'Veg', egg: 'Egg', nonveg: 'Non-veg' } as const;
</script>

<template>
    <h2 class="sheet-title">{{ recipe.name }}</h2>
    <p v-if="recipe.nameHi" class="sheet-hi" lang="hi">{{ recipe.nameHi }}</p>
    <div class="chiprow">
        <span class="diet-label"
            ><DietMark :diet="recipe.diet" />{{ DIET[recipe.diet] }}</span
        >
        <MoodTag :mood="recipe.mood" :label="recipe.moodLabel" />
        <span class="pill">{{ recipe.cuisine }}</span>
        <span class="pill">{{ SPICE[recipe.spice] }}</span>
        <span class="pill">{{ EFFORT[recipe.effort - 1] }}</span>
    </div>
    <p class="timing">
        {{
            recipe.minutes <= 2
                ? 'Ready to serve, no cooking'
                : `Prep ${recipe.prep} min, ${recipe.cook ? `cook ${recipe.cook} min` : 'no cooking'}`
        }}
    </p>
    <div class="nutri">
        <div>
            <b>{{ recipe.kcal }}</b
            ><span>kcal</span>
        </div>
        <div>
            <b>{{ recipe.protein }}g</b><span>protein</span>
        </div>
        <div>
            <b>{{ recipe.carbs }}g</b><span>carbs</span>
        </div>
        <div>
            <b>{{ recipe.fat }}g</b><span>fat</span>
        </div>
        <div>
            <b>{{ recipe.fibre }}g</b><span>fibre</span>
        </div>
    </div>
    <p class="small muted" style="margin-top: 6px">Per serving, approximate.</p>
    <p class="contains">
        {{
            recipe.allergens.length
                ? `Contains ${recipe.allergens.join(', ')}.`
                : 'No dairy, gluten, nuts, soy or fish.'
        }}
    </p>
    <slot name="extra" />
    <h3>Raw items for {{ forWho }}</h3>
    <ul class="ing">
        <li v-for="item in recipe.ingredients" :key="item.name">
            <span>{{ item.name }}</span
            ><span>{{ item.quantity }}</span>
        </li>
    </ul>
    <h3>How to make it</h3>
    <ol class="steps">
        <li v-for="(step, i) in recipe.steps" :key="i">{{ step }}</li>
    </ol>
    <slot name="actions" />
    <p class="sr-only">{{ plural(recipe.servings, 'serving', 'servings') }}</p>
</template>
