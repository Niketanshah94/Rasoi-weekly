<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '@/components/rz/AppShell.vue';
import Sheet from '@/components/rz/Sheet.vue';
import { copyText, whatsAppUrl } from '@/lib/share';
import { tick } from '@/routes/groceries';
import type { HouseholdSummary, Shell } from '@/types/app';
import { plural } from '@/types/app';

const props = defineProps<{
    shell: Shell;
    household: HouseholdSummary;
    planName: string;
    listName: string;
    spicesNote: string;
    groups: {
        aisle: string;
        items: { key: string; name: string; quantity: string; have: boolean }[];
    }[];
    message: { en: string; hi: string | null };
    cookPhone: string | null;
}>();

const items = computed(() => props.groups.flatMap((g) => g.items));
const have = computed(() => items.value.filter((i) => i.have).length);
const missing = computed(() => items.value.length - have.value);
const pct = computed(() =>
    items.value.length
        ? Math.round((have.value / items.value.length) * 100)
        : 0,
);
const listTitle = computed(
    () => props.listName.charAt(0).toUpperCase() + props.listName.slice(1),
);

const sheetOpen = ref(false);
const lang = ref<'en' | 'hi'>('en');
const text = computed(() =>
    lang.value === 'hi' && props.message.hi
        ? props.message.hi
        : props.message.en,
);
const copied = ref(false);

function setHave(key: string, value: boolean) {
    router.post(
        tick.url(),
        { ingredient: key, have: value },
        { preserveScroll: true, preserveState: true },
    );
}

async function copy() {
    copied.value = await copyText(text.value);
}
</script>

<template>
    <Head title="Groceries" />

    <AppShell :shell="shell" tab="groceries">
        <div class="g-head block">
            <h2>Shopping list</h2>
            <p class="muted">
                {{ planName }}, all 7 days, for
                {{ household.summary.toLowerCase() }}.
                <Link href="/settings/household" class="linkbtn"
                    >Edit household</Link
                >
            </p>
        </div>
        <div class="progress">
            <div class="bar"><i :style="{ width: `${pct}%` }" /></div>
            <p>
                {{ have }} of {{ items.length }} items already in your kitchen.
                {{ spicesNote }} aren’t listed.
            </p>
        </div>
        <section v-for="g in groups" :key="g.aisle" class="g-group">
            <h3>{{ g.aisle }}</h3>
            <ul>
                <li v-for="item in g.items" :key="item.key">
                    <label>
                        <input
                            type="checkbox"
                            :checked="item.have"
                            @change="
                                setHave(
                                    item.key,
                                    ($event.target as HTMLInputElement).checked,
                                )
                            "
                        />
                        <span class="g-name">{{ item.name }}</span
                        ><span class="g-qty">{{ item.quantity }}</span>
                    </label>
                </li>
            </ul>
        </section>
        <button
            type="button"
            class="btn primary wide"
            :disabled="!missing"
            data-test="send-list"
            @click="sheetOpen = true"
        >
            {{
                missing
                    ? `Send ${listName} on WhatsApp`
                    : 'Everything’s in your kitchen'
            }}
        </button>
        <p v-if="missing" class="fine center">
            {{ missing }} {{ plural(missing, 'item', 'items') }} to buy. Anyone
            can shop from it, no app needed.
        </p>

        <template #sheets>
            <Sheet v-model="sheetOpen" :title="listTitle">
                <h2 class="sheet-title" style="font-size: 24px">
                    Send the {{ listName }}
                </h2>
                <p class="muted" style="margin-top: 6px">
                    {{ missing }} {{ plural(missing, 'item', 'items') }} you
                    don’t have yet, grouped by section. Send it to whoever is
                    going to the market; they don’t need the app.
                </p>
                <div
                    v-if="message.hi"
                    class="seg"
                    role="group"
                    aria-label="List language"
                >
                    <button
                        type="button"
                        :aria-pressed="lang === 'en'"
                        @click="lang = 'en'"
                    >
                        English
                    </button>
                    <button
                        type="button"
                        :aria-pressed="lang === 'hi'"
                        lang="hi"
                        @click="lang = 'hi'"
                    >
                        हिंदी
                    </button>
                </div>
                <div class="wa">
                    <p class="wa-to">Message preview</p>
                    <div
                        class="wa-bubble scroll"
                        :lang="lang === 'hi' ? 'hi' : undefined"
                    >
                        {{ text }}
                    </div>
                </div>
                <div class="bazaar-actions">
                    <a
                        class="btn primary big wide"
                        :href="whatsAppUrl(text)"
                        target="_blank"
                        rel="noopener"
                        >Choose a WhatsApp contact</a
                    >
                </div>
                <div
                    class="sheet-actions"
                    :class="{ stack: !cookPhone }"
                    style="margin-top: 10px"
                >
                    <a
                        v-if="cookPhone"
                        class="btn"
                        :href="whatsAppUrl(text, cookPhone)"
                        target="_blank"
                        rel="noopener"
                        >Send to {{ household.cookName || 'your cook' }}</a
                    >
                    <button type="button" class="btn" @click="copy">
                        {{ copied ? 'Copied' : 'Copy list' }}
                    </button>
                </div>
            </Sheet>
        </template>
    </AppShell>
</template>
