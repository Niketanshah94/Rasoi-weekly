<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '@/components/rz/AppShell.vue';
import DietMark from '@/components/rz/DietMark.vue';
import Sheet from '@/components/rz/Sheet.vue';
import { copyText, whatsAppUrl } from '@/lib/share';
import { cooked as cookedRoute } from '@/routes/plan';
import type { DishCard, HouseholdSummary, Shell } from '@/types/app';
import { plural, timeLabel } from '@/types/app';

type CookMeal = {
    meal: string;
    label: string;
    labelHi: string;
    time: string;
    dish: DishCard;
    pair: DishCard | null;
    portions: number;
    steps: string[];
    cooked: boolean;
};

const props = defineProps<{
    shell: Shell;
    day: number;
    household: HouseholdSummary;
    cookPhone: string | null;
    cookPhoneDisplay: string | null;
    cookLanguage: 'hi' | 'en';
    week: {
        hasHindi: boolean;
        hindiDay: string;
        message: { en: string; hi: string };
        meals: CookMeal[];
    }[];
}>();

const dayIndex = ref(props.day);
const today = computed(() => props.week[dayIndex.value]);
const lang = ref<'en' | 'hi'>(props.cookLanguage);
const hindi = computed(() => today.value.hasHindi && lang.value === 'hi');
const share = computed(() => props.household.whoCooks !== 'self');
const self = computed(() => props.household.whoCooks !== 'cook');
const fullDay = computed(() => props.shell.days[dayIndex.value].full);

const title = computed(() => {
    if (hindi.value) {
        return `${today.value.hindiDay} का खाना`;
    }

    return share.value && !self.value
        ? `${fullDay.value}’s menu`
        : `Cook ${fullDay.value}’s meals`;
});
const sub = computed(() => {
    if (share.value && !self.value) {
        return `Big text${today.value.hasHindi ? ' and Hindi names' : ''}${props.household.cookName ? `, ready for ${props.household.cookName}` : ''}.`;
    }

    return share.value
        ? 'Cook step by step, or send the menu to your cook.'
        : 'Step-by-step recipes for every meal.';
});
const message = computed(() =>
    hindi.value ? today.value.message.hi : today.value.message.en,
);

const sheet = ref<'share' | 'cook' | null>(null);
const sheetOpen = computed({
    get: () => sheet.value !== null,
    set: (v) => !v && (sheet.value = null),
});
const cooking = ref<CookMeal | null>(null);
const step = ref(0);
const copied = ref(false);

function startCooking(m: CookMeal) {
    cooking.value = m;
    step.value = 0;
    sheet.value = 'cook';
}

function done() {
    if (cooking.value) {
        router.post(
            cookedRoute.url(),
            { day: dayIndex.value, meal: cooking.value.meal, cooked: true },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => (sheet.value = null),
            },
        );
    }
}

async function copy() {
    copied.value = await copyText(message.value);
}
</script>

<template>
    <Head title="Cook" />

    <AppShell v-model:day="dayIndex" :shell="shell" tab="cook" show-days>
        <div class="cook-head block">
            <h2 :lang="hindi ? 'hi' : undefined">{{ title }}</h2>
            <p class="muted">{{ sub }}</p>
        </div>
        <div
            v-if="today.hasHindi"
            class="seg"
            role="group"
            aria-label="Menu language"
        >
            <button type="button" :aria-pressed="!hindi" @click="lang = 'en'">
                English
            </button>
            <button
                type="button"
                :aria-pressed="hindi"
                lang="hi"
                @click="lang = 'hi'"
            >
                हिंदी
            </button>
        </div>
        <button
            v-if="share"
            type="button"
            class="btn primary wide"
            @click="sheet = 'share'"
        >
            Share with {{ household.cookName || 'your cook' }} on WhatsApp
        </button>
        <ol class="cook-list">
            <li v-for="m in today.meals" :key="m.meal">
                <div class="c-top">
                    <span :lang="hindi ? 'hi' : undefined">{{
                        hindi ? m.labelHi : m.label
                    }}</span
                    ><span>{{ m.time }}</span>
                </div>
                <span class="c-dish" :lang="hindi ? 'hi' : undefined"
                    ><DietMark :diet="m.dish.diet" />{{
                        hindi && m.dish.nameHi ? m.dish.nameHi : m.dish.name
                    }}</span
                >
                <span
                    v-if="m.pair"
                    class="pair"
                    :lang="hindi ? 'hi' : undefined"
                    ><DietMark :diet="m.pair.diet" />{{
                        hindi && m.pair.nameHi
                            ? `शाकाहारी: ${m.pair.nameHi}`
                            : `Veg: ${m.pair.name}`
                    }}</span
                >
                <span class="c-meta" :lang="hindi ? 'hi' : undefined">
                    {{
                        hindi
                            ? `${m.dish.minutes <= 2 ? 'पकाना नहीं है' : `${m.dish.minutes} मिनट`}, ${m.portions} लोगों के लिए`
                            : `${timeLabel(m.dish.minutes)}, for ${m.portions} ${plural(m.portions, 'person', 'people')}`
                    }}
                </span>
                <details class="c-steps">
                    <summary :lang="hindi ? 'hi' : undefined">
                        {{ hindi ? 'बनाने का तरीका' : 'How to make it' }}
                    </summary>
                    <ol>
                        <li v-for="(s, i) in m.steps" :key="i">{{ s }}</li>
                    </ol>
                </details>
                <div v-if="self && m.dish.minutes > 2" class="cook-actions">
                    <button
                        type="button"
                        class="btn small"
                        @click="startCooking(m)"
                    >
                        {{ m.cooked ? 'Cook again' : 'Start cooking' }}
                    </button>
                </div>
            </li>
        </ol>

        <template #sheets>
            <Sheet
                v-model="sheetOpen"
                :title="
                    sheet === 'share'
                        ? 'Share the menu'
                        : (cooking?.label ?? '')
                "
            >
                <template v-if="sheet === 'share'">
                    <div class="wa">
                        <p class="wa-to">
                            To {{ household.cookName || 'your cook'
                            }}{{
                                cookPhoneDisplay ? `, ${cookPhoneDisplay}` : ''
                            }}
                        </p>
                        <div class="wa-bubble" :lang="hindi ? 'hi' : undefined">
                            {{ message }}
                        </div>
                    </div>
                    <p class="fine">
                        Change the language with the English and हिंदी switch on
                        the cook tab.
                    </p>
                    <div class="sheet-actions">
                        <button type="button" class="btn" @click="copy">
                            {{ copied ? 'Copied' : 'Copy text' }}
                        </button>
                        <a
                            class="btn primary"
                            :href="whatsAppUrl(message, cookPhone)"
                            target="_blank"
                            rel="noopener"
                            >Send on WhatsApp</a
                        >
                    </div>
                </template>
                <template v-else-if="cooking">
                    <h2 class="sheet-title">{{ cooking.dish.name }}</h2>
                    <div class="cm-dots" aria-hidden="true">
                        <i
                            v-for="(_, j) in cooking.steps"
                            :key="j"
                            :class="{ on: j <= step }"
                        />
                    </div>
                    <p class="cm-count">
                        Step {{ step + 1 }} of {{ cooking.steps.length }}
                    </p>
                    <p class="cm-step" aria-live="polite">
                        {{ cooking.steps[step] }}
                    </p>
                    <div class="sheet-actions">
                        <button
                            type="button"
                            class="btn"
                            :disabled="step === 0"
                            @click="step--"
                        >
                            Back
                        </button>
                        <button
                            v-if="step === cooking.steps.length - 1"
                            type="button"
                            class="btn primary"
                            @click="done"
                        >
                            Done, mark as cooked
                        </button>
                        <button
                            v-else
                            type="button"
                            class="btn primary"
                            @click="step++"
                        >
                            Next step
                        </button>
                    </div>
                </template>
            </Sheet>
        </template>
    </AppShell>
</template>
