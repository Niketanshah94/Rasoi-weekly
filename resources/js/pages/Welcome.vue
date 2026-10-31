<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DietMark from '@/components/rz/DietMark.vue';
import { countryFromTimeZone } from '@/lib/country';
import { login, register } from '@/routes';
import type { DishCard } from '@/types/app';

type LandingCountry = {
    code: string;
    family: string;
    people: string;
    preview: { time: string; dish: DishCard | null; pair: DishCard | null }[];
    dishCount: number;
    cuisineCount: number;
    leaveOuts: string;
    listName: string;
    zero: string;
    monthly: string;
    yearly: string;
    yearlyPerMonth: string;
    charged: { monthly: string; yearly: string } | null;
    save: number;
    taxNote: string;
    paymentDetails: string;
};

const props = defineProps<{ countries: LandingCountry[] }>();

const c = computed(() => {
    const code = countryFromTimeZone(props.countries.map((x) => x.code));

    return props.countries.find((x) => x.code === code) ?? props.countries[0];
});

const faq = computed(() => [
    [
        `Why do you ask for ${c.value.paymentDetails} details for a free trial?`,
        'It sets up AutoPay so your plan carries on without a break. Nothing is charged for 7 days, we remind you on day 5, and you can cancel in two taps.',
    ],
    [
        'Does it work if some of us eat non-veg and some don’t?',
        'Yes. Tell us how many people eat non-veg. Every non-veg meal comes with a veg dish, and the shopping list splits the portions.',
    ],
    [
        'Can my cook use it?',
        'Yes. Share the day’s menu on WhatsApp in Hindi or English, with step-by-step instructions for every dish.',
    ],
    [
        'What if I don’t subscribe after the trial?',
        'Recipes stay free. Weekly plans, the shopping list and the cooking tools need a subscription.',
    ],
    [
        'Are the nutrition numbers exact?',
        'They’re estimates per serving, based on standard ingredient values. Use them as a guide, not as medical advice.',
    ],
]);
</script>

<template>
    <Head title="Weekly meal plans for your household" />

    <div class="rz min-h-svh">
        <div class="landing">
            <header class="l-top">
                <span class="brand-word">Rasoi</span>
                <Link :href="login()" class="btn small">Log in</Link>
            </header>
            <section class="hero">
                <h1>What’s cooking this week? Sorted.</h1>
                <p class="lede">
                    Breakfast to late-night cravings, planned around who eats
                    what at home. The shopping list and your cook’s menu come
                    with it.
                </p>
                <Link
                    :href="register()"
                    class="btn primary big wide"
                    data-test="start-trial"
                    >Start 7-day free trial</Link
                >
                <p class="fine">
                    {{ c.zero }} today, then {{ c.monthly }} a month. Cancel
                    anytime.
                </p>
                <div class="preview" role="group" aria-label="Example day">
                    <div class="preview-head">
                        <b>Tuesday at {{ c.family }}</b
                        ><span>{{ c.people }}</span>
                    </div>
                    <ul class="pv-list">
                        <li
                            v-for="(item, i) in c.preview.filter((p) => p.dish)"
                            :key="i"
                        >
                            <span class="pv-time">{{ item.time }}</span>
                            <span>
                                <span class="pv-dish"
                                    ><DietMark :diet="item.dish!.diet" />{{
                                        item.dish!.name
                                    }}</span
                                >
                                <span v-if="item.pair" class="pair"
                                    ><DietMark :diet="item.pair.diet" />Veg:
                                    {{ item.pair.name }}</span
                                >
                            </span>
                        </li>
                    </ul>
                </div>
            </section>
            <section class="l-sec">
                <h2>How it works</h2>
                <ol class="how">
                    <li>
                        <span
                            ><b>Tell us who eats what</b
                            ><span
                                >Veg, egg, non-veg or a mix of all three. Leave
                                out {{ c.leaveOuts }}.</span
                            ></span
                        >
                    </li>
                    <li>
                        <span
                            ><b>Get a full week of meals</b
                            ><span
                                >{{ c.dishCount }} dishes from
                                {{ c.cuisineCount }} cuisines, wholesome and
                                indulgent. Swap anything you don’t fancy.</span
                            ></span
                        >
                    </li>
                    <li>
                        <span
                            ><b>Shop once, cook with ease</b
                            ><span
                                >One {{ c.listName }} for the week, sent on
                                WhatsApp to whoever shops. Cook step by step, or
                                send the day’s menu to your cook on
                                WhatsApp.</span
                            ></span
                        >
                    </li>
                </ol>
            </section>
            <section class="l-sec">
                <h2>Simple pricing</h2>
                <div class="pay-plans">
                    <div class="pay-plan">
                        <span class="pp-name">Monthly</span
                        ><span class="pp-price">{{ c.monthly }}</span
                        ><span class="pp-note">per month</span>
                        <span v-if="c.charged" class="pp-usd"
                            >Charged as {{ c.charged.monthly }}</span
                        >
                    </div>
                    <div class="pay-plan">
                        <span class="save-tag">Save {{ c.save }}%</span>
                        <span class="pp-name">Yearly</span
                        ><span class="pp-price">{{ c.yearly }}</span
                        ><span class="pp-note"
                            >per year. Works out to {{ c.yearlyPerMonth }} a
                            month</span
                        >
                        <span v-if="c.charged" class="pp-usd"
                            >Charged as {{ c.charged.yearly }}</span
                        >
                    </div>
                </div>
                <p class="fine">
                    Both start with a 7-day free trial. {{ c.taxNote }} Recipes
                    stay free even if you don’t subscribe.
                </p>
            </section>
            <section class="l-sec faq">
                <h2>Questions</h2>
                <details v-for="[q, a] in faq" :key="q">
                    <summary>{{ q }}</summary>
                    <p>{{ a }}</p>
                </details>
            </section>
            <footer class="l-foot">
                <Link :href="`/legal/terms?country=${c.code}`" class="linkbtn"
                    >Terms</Link
                >
                <Link :href="`/legal/privacy?country=${c.code}`" class="linkbtn"
                    >Privacy</Link
                >
                <Link :href="`/legal/refund?country=${c.code}`" class="linkbtn"
                    >Refunds and cancellation</Link
                >
            </footer>
        </div>
    </div>
</template>
