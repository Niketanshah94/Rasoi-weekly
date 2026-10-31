<script setup lang="ts">
import { computed } from 'vue';
import type { PlanDay, Shell } from '@/types/app';
import { MOOD_COLOR, STATUS_COLOR } from '@/types/app';

/**
 * The day's plate: calories split by meal, the healthy-day check, the fix and the week strip (spec §6).
 */
const props = defineProps<{
    day: PlanDay;
    dayIndex: number;
    week: PlanDay[];
    shell: Shell;
    healthyDays: number;
    isMixed: boolean;
}>();

defineEmits<{ selectDay: [index: number]; fix: [] }>();

const R = 46;
const C = 2 * Math.PI * R;
const GAP = 3;

const segments = computed(() => {
    let start = 0;

    return props.day.meals.map((m) => {
        const len = (C * m.dish.kcal) / props.day.kcal;
        const seg = {
            color: MOOD_COLOR[m.dish.mood],
            dash: `${Math.max(len - GAP, 0)} ${C}`,
            offset: -start,
        };
        start += len;

        return seg;
    });
});
const cookedCount = computed(
    () => props.day.meals.filter((m) => m.cooked).length,
);
const kcal = computed(() => props.day.kcal.toLocaleString('en-IN'));
</script>

<template>
    <div class="plate">
        <div class="plate-top">
            <svg
                viewBox="0 0 132 132"
                role="img"
                :aria-label="`Calories split across the day’s meals: ${day.kcal} kcal per person`"
            >
                <circle
                    cx="66"
                    cy="66"
                    r="63"
                    fill="none"
                    stroke-width="3"
                    style="stroke: var(--line)"
                />
                <circle
                    cx="66"
                    cy="66"
                    :r="R"
                    fill="none"
                    stroke-width="18"
                    style="stroke: var(--surface-2)"
                />
                <circle
                    v-for="(s, i) in segments"
                    :key="i"
                    cx="66"
                    cy="66"
                    :r="R"
                    fill="none"
                    stroke-width="18"
                    :style="{ stroke: s.color }"
                    :stroke-dasharray="s.dash"
                    :stroke-dashoffset="s.offset"
                    transform="rotate(-90 66 66)"
                />
                <text
                    x="66"
                    y="66"
                    text-anchor="middle"
                    style="
                        fill: var(--ink);
                        font-family: var(--display);
                        font-weight: 600;
                        font-size: 22px;
                    "
                >
                    {{ kcal }}
                </text>
                <text
                    x="66"
                    y="83"
                    text-anchor="middle"
                    style="
                        fill: var(--muted);
                        font-family: var(--body);
                        font-size: 11px;
                    "
                >
                    kcal each
                </text>
            </svg>
            <div>
                <h2>{{ shell.days[dayIndex].full }}’s plate</h2>
                <p class="small muted">
                    {{ cookedCount }} of 5 cooked, per person{{
                        isMixed ? ', main dishes' : ''
                    }}
                </p>
                <p
                    class="hstatus"
                    :style="{ '--st': STATUS_COLOR[day.status] }"
                >
                    <i aria-hidden="true" />{{ day.statusLabel }}
                </p>
                <p class="small muted">
                    {{ day.met }} of 5 healthy targets met
                </p>
            </div>
        </div>
        <div class="legend" aria-hidden="true">
            <span><i style="background: var(--leaf)" />Wholesome</span
            ><span><i style="background: var(--turmeric)" />Comfort</span
            ><span><i style="background: var(--chili)" />Treat</span>
        </div>
        <ul
            class="hchecks"
            :aria-label="`Healthy targets for ${shell.days[dayIndex].full}`"
        >
            <li v-for="c in day.checks" :key="c.label">
                <span class="hc-name">{{ c.label }}</span>
                <span class="hc-val"
                    >{{ c.value }}<small>Target: {{ c.target }}</small></span
                >
                <span class="hc-state" :class="`hc-${c.state}`">{{
                    c.stateLabel
                }}</span>
            </li>
        </ul>
        <p class="praise">{{ day.praise }}</p>
        <div v-if="day.fix" class="hfix">
            <p>
                Try <b>{{ day.fix.withName }}</b> instead of
                {{ day.fix.replace }} for {{ day.fix.mealLabel }}. That meets
                {{ day.fix.met }} of 5 targets.
            </p>
            <button
                type="button"
                class="btn small"
                data-test="apply-fix"
                @click="$emit('fix')"
            >
                Swap
            </button>
        </div>
        <div class="hweek">
            <p class="small">
                <b>This week:</b> {{ healthyDays }} of 7 days healthy
            </p>
            <div
                class="hweek-days"
                role="group"
                aria-label="Healthy days this week"
            >
                <button
                    v-for="(d, i) in week"
                    :key="i"
                    type="button"
                    class="hday"
                    :class="{ sel: i === dayIndex }"
                    :style="{ '--st': STATUS_COLOR[d.status] }"
                    :aria-label="`${shell.days[i].full}: ${d.statusLabel}`"
                    :aria-current="i === dayIndex ? 'date' : undefined"
                    @click="$emit('selectDay', i)"
                >
                    <i aria-hidden="true" /><span>{{
                        shell.days[i].short
                    }}</span>
                </button>
            </div>
            <div class="legend" aria-hidden="true">
                <span><i style="background: var(--leaf)" />Healthy</span
                ><span><i style="background: var(--turmeric)" />Nearly</span
                ><span><i style="background: var(--chili)" />Unbalanced</span>
            </div>
        </div>
        <p class="fine">
            Targets are general guides for an average adult. Needs vary with
            age, body size, activity and health conditions.
        </p>
    </div>
</template>
