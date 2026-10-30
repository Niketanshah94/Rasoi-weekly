# Rasoi product spec

Rules taken from the prototype (`prototype/index.html`). Numbers and names come from `data/*.json`.

## 1. Accounts and sign-in

- Sign up or log in with **Google** or a **one-time login link by email** (valid 15 minutes, single use). No passwords, no SMS.
- **Country** is chosen on the sign-up screen only (India, US, UK, UAE, Singapore), pre-selected from the device time zone. It sets dishes, plans, prices, units, payment methods and phone formats. It can be changed later in Household settings.
- Logging in with an email that has no account switches the user to sign-up with that email filled in.
- In the mobile apps, Google sign-in uses the native plugin; the app sends the Google ID token to `POST /api/auth/google`, which returns a Sanctum token and starts a web session.
- **WhatsApp number** is optional and added in Account: enter number (country dial code and format from `countries.json`), receive a 6-digit code on WhatsApp, verify. It can be changed or removed. Without it, every notification goes by email.
- Account deletion is self-serve in Account: deletes household, plans and lists, cancels any subscription and revokes the mandate. Payment records are kept as tax law requires.

## 2. Onboarding (in this order)

1. Name (pre-filled from Google).
2. Who cooks: `self`, `cook` or `both`.
3. Who's eating: number of people (1 to 12), number who eat chicken, mutton or fish (0 to people), and whether the others eat eggs.
4. Leave out: dairy, gluten, nuts everywhere; also beef and pork outside India.
5. Plan style: plans available for the country (`plans.json`), each showing Monday's breakfast, lunch and dinner after diet and leave-out substitution.
6. Only if a cook is involved: cook's name, cook's WhatsApp number (optional), menu language (Hindi or English).

Then the free-trial offer (section 7).

## 3. Diet

**Household diet mode** from the household:
- all eat non-veg → `nonveg`
- none eat non-veg → `egg` if the others eat eggs, else `veg`
- some eat non-veg → `mixed`

**Dish diet** from its ingredients: any ingredient in the "Meat & fish" aisle → `nonveg`; otherwise any `eggs` → `egg`; otherwise `veg`. Allergens are the union of the ingredients' allergens.

**A dish is allowed for a household when all of these hold:**
- It is published.
- It is in the country's pool: the dish's countries include the household's country, or the household is outside India and the dish includes India (Indian dishes are available everywhere).
- Diet fits: `nonveg` and `mixed` households allow everything; `egg` allows veg and egg dishes; `veg` allows veg dishes only.
- No leave-out matches: "nuts" matches tree nuts or peanuts; every other leave-out matches its own allergen code.

## 4. Building a household's week

Each plan template (`plans.json`) names a dish for every meal of every day. For each meal (breakfast, lunch, snack, dinner, late night), over the seven days:

1. Count how many times each **allowed** template dish appears that week.
2. Candidate pool: allowed dishes for that meal. If the pool is empty, use dishes that fit the diet and country, ignoring leave-outs.
3. Keep a template dish if it is allowed. Otherwise replace it with the candidate with the **lowest score**:
   `score = times_used_this_week × 100 + |protein difference| + (mood differs ? 15 : 0) + (dish not from the preferred country ? 40 : 0)`
   The preferred country is India for the "Indian at home" plan and the household's own country for every other plan. Ties go to the candidate that comes first in `data/dishes.json` order (seed dishes in that order and keep it). Add 1 to the chosen dish's use count.
4. A user's swap or "add to this meal" overrides the resolved dish for that plan, diet mode, day and meal. If the override is no longer allowed (diet or leave-outs changed, dish hidden), fall back to the resolved dish.

**Mixed households:** every non-veg dish gets a paired dish for the veg eaters, chosen from dishes allowed for the veg side (diet `egg` if they eat eggs, else `veg`) in the same meal, with the lowest score:
`|protein difference| + (mood differs ? 12 : 0) + (already used in another meal that day ? 60 : 0) + ((candidate index × 7 + day index × 3) mod 5) + (not from the preferred country ? 40 : 0)`
"Candidate index" is the position in the candidate list, in `data/dishes.json` order. The modulo term adds deterministic variety. Keep it so results match the golden fixtures.

**Portions:** in a mixed household the non-veg dish is cooked for the non-veg eaters and the paired dish for the veg eaters; everything else for the whole household.

`data/golden/plan_resolution.json` holds the expected results. Treat any difference as a bug unless the spec is deliberately changed.

## 5. Shopping list (bazaar list in India)

- Sum each ingredient over the 7 days using the portions above.
- Group by aisle in this order: Vegetables & herbs, Fruit, Dairy & eggs, Grains & flours, Dals & pulses, Meat & fish, Bread & ready-made, Pantry. Sort by name within each aisle.
- Ingredient names use the country's local names (`local_names` in `ingredients.json`); Hindi names are available for India.
- Display quantities:
  - Metric: grams and ml rounded up to the nearest 5 below 100 and the nearest 10 below 1,000; from 1,000 show kg or L with one decimal.
  - US: grams as oz (rounded up), switching to lb in quarter steps from 16 oz; ml as fl oz (rounded up), switching to quarts in half steps from 32 fl oz.
  - Pieces round up to whole numbers; slices, packs, bunches, cups and cans round up and pluralise.
- Households tick items they already have; ticks are per plan and week.
- "Send on WhatsApp": a plain-text list grouped by aisle, in English, or in Hindi for India and the Indian plan, sent to any contact the user picks, or straight to the cook if a number is saved. Format: header with week and people count, sections, `• name: quantity`, footer "Sent from Rasoi".

## 6. Nutrition and the healthy-day check

Per person per day, over the five meals (main dishes in mixed homes):

| Check | Target |
|---|---|
| Calories | 1,600 to 2,400 kcal |
| Protein | 50 g or more |
| Fibre | 25 g or more |
| Fat | 35% of calories or less (fat g × 9 ÷ kcal) |
| Carbs | 45% to 65% of calories (carbs g × 4 ÷ kcal) |

- 5 met → "Healthy day"; 4 → "Nearly balanced"; fewer → "Unbalanced day".
- **One-tap fix:** when fewer than 5 are met, try every allowed dish for every meal (excluding dishes already in that day) and pick the single swap that meets the most targets. Ties go to the smallest calorie change, with a 60-point penalty when the mood changes. Only suggest it if it meets more targets than now.
- The week strip shows each day's status; tapping a day opens it.
- Copy is encouraging, never shaming. A day with 2 or more treats adds "Treat days happen; it's the week that counts."
- Footnote: targets are general guides for an average adult.

## 7. Trial, subscription and access

**Prices** (`pricing.json`):
- India: ₹149 a month or ₹1,199 a year, including GST, through Razorpay.
- Everywhere else: US$5.99 a month or US$49.99 a year through Polar. Prices are shown in local currency at the day's exchange rate, always with "Charged as $5.99", and checkout shows the real USD amount. Tax is calculated at checkout.
- Inside the iOS and Android apps: Apple or Google in-app purchase through RevenueCat, at matching price points.

**Trial timeline:**

| Day | What happens |
|---|---|
| 1 | Trial starts. Payment mandate or method set up (UPI AutoPay or card in India; card, Apple Pay or Google Pay elsewhere). Nothing charged. |
| 5 | Reminder: two days left, with the first charge date and amount. |
| 7 | Pre-debit notice at least 24 hours before the charge. |
| 8 | First charge, then every month or year. |

One trial per email address and per payment method. "Not now, just browse recipes" skips the trial and lands on the free tier.

**Statuses:** `trialing`, `active`, `past_due`, `cancelled`, `expired`, plus `free` for accounts with no subscription.
- Cancel (trial or paid) → `cancelled` with access until the end of the trial or paid period, then `expired`. Resume before then restores the previous state.
- A failed renewal → `past_due` with a 3-day grace period and a banner; updating the payment method retries immediately.
- Changing plan during the trial changes the first charge; changing a paid plan applies from the next renewal.
- The cancel flow asks for a reason. "Too expensive" on monthly offers the yearly plan; "Didn't like the dishes" offers another plan style.

**Access:** `trialing`, `active`, `past_due` and `cancelled` (before its end date) get everything. `free` and `expired` keep the dish library and recipes only; Plan, Groceries and Cook show a subscribe screen.

## 8. Cooking view

- Self-cooking households: step-by-step mode, one step per screen, Back and Next, "Done, mark as cooked" at the end, screen kept awake in the apps.
- Cook households: big text, English or Hindi dish names, and "Share with {cook} on WhatsApp". The message lists each meal with its time and dish, including the veg pairing in mixed homes; Hindi uses Hindi meal and dish names.
- Marking a meal as cooked shows a short compliment; all five cooked shows a special one.

## 9. Admin panel

Staff-only, web only, every change written to an activity log.

- **Dashboard:** accounts, new this week, active trials, paying customers, failed payments, monthly recurring revenue in USD and INR, trial-to-paid rate, revenue in the last 30 days, sign-ups per day, accounts by country, failed payments to follow up, live dishes per country.
- **Users:** search, filter by status and country, detail with household, subscription and payments. Actions: extend trial by 7 days, give a free month, cancel, reactivate, mark a failed payment as recovered, send a reminder or win-back offer, refund the last payment, resend a login link, delete.
- **Dishes:** create and edit names, status (live, draft, hidden), countries, meals, mood, cuisine, times, effort, spice, nutrition, ingredients with quantities and steps. Show the derived diet and allergens live, and warn when calories differ from protein × 4 + carbs × 4 + fat × 9 by more than 15%. Hiding a dish makes plans substitute it automatically. A dish used in a plan cannot be deleted, only hidden.
- **Plans:** edit name, description and every meal of every day from live dishes valid for that meal and country, with each day's healthy-day status.
- **Pricing:** India rupee prices, the two USD prices and exchange rates, with a preview per country and a reminder to change the matching Polar and store products.
- **Ingredients:** full list with unit, aisle, allergens, Hindi and local names, and how many dishes use each.

## 10. Notifications

| Message | Channel | When |
|---|---|---|
| Login link | Email | On request |
| Trial reminder | WhatsApp if added, else email | Day 5 |
| Pre-debit notice | Email, plus WhatsApp if added | 24+ hours before every charge |
| Receipt or GST invoice | Email, plus WhatsApp if added | After every charge |
| Payment failed | Email, plus WhatsApp if added | On failure |
| Next week's plan is ready | Push or WhatsApp | Sundays 6 pm, if switched on |
| Prep reminder | Push or WhatsApp | Evening before, for dishes that need soaking or marinating, if switched on |

## 11. Countries

`countries.json` has, per country: dial code, mobile number pattern and length, billing provider, web payment methods, metric or US units, the shopping list's name ("bazaar list" in India), tax wording, receipt name and the privacy law named in the privacy policy. Legal pages are drafts in the prototype; lawyers write the final text per country.
