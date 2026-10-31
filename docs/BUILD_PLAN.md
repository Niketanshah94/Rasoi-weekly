# Build plan

Work through these in order. Each milestone ends with passing tests and a pull request. Paste the starter prompt into Claude Code to begin a milestone; ask it to show a plan before it writes code.

---

## M0. Project setup

**Goal:** a running Laravel app with the chosen stack, tests and CI.

- The repo already holds `CLAUDE.md`, `docs/`, `data/` and `prototype/`. Scaffold Laravel in a temporary folder and move it in, keeping those.
- New Laravel app with Inertia + Vue 3 + Tailwind (official starter kit if it fits), Pest, Filament, Sanctum, Horizon.
- MySQL or PostgreSQL and Redis for local development; `.env.example` complete.
- GitHub Actions: install, build assets, run Pest on every push.
- `CLAUDE.md` "Commands" section filled in.

**Done when:** the welcome page loads, `admin.` subdomain shows the Filament login, the test suite passes in CI.

> Starter prompt: "Read CLAUDE.md and docs/BUILD_PLAN.md. Plan milestone M0: check current Laravel, Inertia, Vue, Filament and Pest versions, then scaffold the project. Show me the plan before you run anything."

---

## M1. Content model and seed data

**Goal:** dishes, ingredients, plans and countries in the database, matching the prototype.

- Tables for countries, ingredients (with local names and Hindi names), dishes (with countries, meals, status), dish ingredients, plan templates and template items, pricing settings, exchange rates.
- Seeders that load `data/*.json` in file order.
- Model logic for derived diet and allergens (spec §3).
- Tests: every dish's derived diet and allergens equal `derived_for_reference` in `dishes.json`.

**Done when:** a fresh migrate + seed loads 187 dishes, 147 ingredients and 13 plans, and the derivation tests pass.

> Starter prompt: "Start M1. Design migrations and models for the content in data/*.json, write seeders that keep file order, and implement diet and allergen derivation per docs/PRODUCT_SPEC.md §3. Add tests that compare against derived_for_reference."

---

## M2. Admin panel

**Goal:** staff can manage content, plans and prices.

- Separate `staff` guard and table; roles: owner, editor, support (a permissions package is fine).
- Filament resources: Dishes (status, countries, meals, ingredient repeater, steps, live diet/allergen preview, calorie check), Ingredients (read, then edit), Plan templates (day-by-day editor with healthy-day status), Pricing and exchange rates (with per-country preview and the Polar reminder), Countries (read).
- Activity log on every admin change.
- A dish used in a plan cannot be deleted, only hidden.

**Done when:** an editor can create a dish, publish it, put it in a plan, and see the change in the database; every change appears in the activity log.

> Starter prompt: "Start M2. Build the Filament admin per docs/PRODUCT_SPEC.md §9, using the admin screens in prototype/index.html#admin as the reference. Users, subscriptions and the dashboard come later in M7; build content, plans and pricing now."

---

## M3. Accounts and sign-in

**Goal:** customers can sign up and log in.

- Google sign-in (Socialite) and one-time email login links (signed, single use, 15 minutes).
- Country on the sign-up screen; unknown email on login switches to sign-up.
- Account page basics: name, email, country; delete account.
- `POST /api/auth/google` for the mobile apps (verify the Google ID token, issue a Sanctum token, start a session).

**Done when:** feature tests cover sign-up, login, expired and reused links, unknown email, and deletion.

> Starter prompt: "Start M3. Implement sign-in per docs/PRODUCT_SPEC.md §1 with the screens from the prototype (landing, sign-up, check your inbox). Include the mobile token-exchange endpoint."

---

## M3b. Android test build

**Goal:** an Android app you can install on a phone and sign in with, built from the same Vue code as the web app.

*Started early, after M0:* the Capacitor project, the connect screen, the debug signing key and the GitHub Actions APK build already exist (see `mobile/README.md`). What remains is below from native Google sign-in on.

- Capacitor project in `/mobile` (Android only for now) loading the customer web app from a staging URL; for local work, live reload against the dev server on the same network.
- Native Google sign-in plugin → `POST /api/auth/google`; email login links open the app (Android App Links).
- Safe-area handling, status bar and splash screen, app icon, offline fallback screen.
- A signed debug APK built by GitHub Actions on every push to `main`, downloadable from the run.

**Done when:** the APK installs on a real Android phone, opens the app, and a user can sign up and log in with Google and with an email link.

> Starter prompt: "Start M3b. Set up Capacitor in /mobile for Android only. Check current plugin options for native Google sign-in before choosing. Tell me what I need to set up in Google Cloud and on my phone."

---

## M4. Households and onboarding

**Goal:** the six onboarding steps and household settings.

- Households, members (owner and member roles, invite later), who cooks, people and non-veg counts, eggs, leave-outs (country-aware), cook details, plan style.
- Household settings screen that edits the same fields, including country.

**Done when:** onboarding matches the prototype step by step, and tests cover diet-mode calculation and country-specific leave-outs.

> Starter prompt: "Start M4. Build onboarding and household settings per docs/PRODUCT_SPEC.md §2 and §3, matching the prototype's screens and copy."

---

## M5. Plan engine and shopping list

**Goal:** the core logic, matching the prototype exactly.

- Resolver, mixed-home pairing, overrides, portions (spec §4).
- Shopping list aggregation, local names, metric and US display rules, pantry ticks (spec §5).
- Healthy-day checks and the one-tap fix (spec §6).
- Golden tests: for every case in `data/golden/plan_resolution.json`, the resolved week, pairs, daily health and weekly groceries must match.

**Done when:** all 192 golden cases pass.

> Starter prompt: "Start M5. Implement the plan engine as plain PHP services with no framework coupling, per docs/PRODUCT_SPEC.md §4 to §6. Write a Pest dataset test over data/golden/plan_resolution.json first, then make it pass."

---

## M6. Customer app screens

**Goal:** every customer screen from the prototype, in Inertia + Vue.

- Plan tab (day strip, plate chart, healthy-day card, meals with pairs, swap sheet, cooked state), Dishes tab (search, filters, sort, recipe sheet, add to plan), Groceries tab, Cook tab (step-by-step mode, share text), Account, legal pages.
- Free-tier gating screens (logic finished in M7).
- Installable web app: manifest, icons, service worker caching the current week's plan and shopping list for offline use.

**Done when:** the app matches the prototype on a 390 px wide phone screen in light and dark mode, and works offline for the current week.

> Starter prompt: "Start M6. Build the customer screens in Vue from prototype/index.html, reusing its layout, tokens and copy. Use the M5 services through Inertia controllers. Start with the Plan tab."

---

## M7. Trial and billing

**Goal:** real subscriptions on the web.

- `BillingProvider` interface; a fake provider for local and tests; Razorpay Subscriptions (India) and Polar (elsewhere) adapters in test mode.
- Paywall and checkout, trial start with mandate, webhooks (signature check, stored raw, queued, idempotent), the status machine (spec §7), cancel, resume, change plan, update payment method.
- Scheduler jobs: day-5 reminder, pre-debit notice, daily exchange rates.
- Receipts and GST invoices.
- Admin: Users and Subscriptions resources with the actions in spec §9, and the dashboard.

**Done when:** a full trial → charge → renewal → failure → recovery → cancel cycle passes against each provider's test mode and the fake provider.

> Starter prompt: "Start M7. Read the current Razorpay Subscriptions and Polar documentation first. Design the BillingProvider interface and the subscription state machine per docs/PRODUCT_SPEC.md §7, build the fake provider and tests, then the real adapters."

---

## M8. Notifications

**Goal:** email, WhatsApp and push.

- Mail templates for login links, reminders, receipts.
- `WhatsAppGateway` interface with a log-only fake, then the chosen provider; number verification by code; day menu and shopping list sharing.
- Push device registration API and sending (FCM/APNs).
- Notification preferences in Account; email fallback when no WhatsApp number.

**Done when:** every message in spec §10 is sent through the right channel, covered by tests with fakes.

> Starter prompt: "Start M8. Implement notifications per docs/PRODUCT_SPEC.md §10 with interfaces and fakes first."

---

## M9. Mobile apps

**Goal:** iOS and Android apps that pass review. The Android shell already exists from M3b; this milestone adds iOS and the store work.

- Add the iOS platform to `/mobile`, pointing both apps at the production URL.
- Native Google sign-in on iOS → `/api/auth/google`.
- Push notifications, share sheet, keep-awake in cooking mode, safe-area handling.
- RevenueCat purchases with matching store products; RevenueCat webhook updates the same subscription record.
- Store listing checklist: privacy labels, account deletion in the app, screenshots.

**Done when:** TestFlight and Play internal testing builds can sign in, subscribe in sandbox and receive a push.

> Starter prompt: "Start M9. Add iOS to the Capacitor project in /mobile. Check current plugin options for push and RevenueCat before choosing. Explain what I need to do in Xcode, Android Studio and the store consoles."

---

## M10. Launch hardening

- Legal pages per country (final text from lawyers), consent at sign-up.
- Rate limits on login links and webhooks, security review, backups.
- PostHog events for the trial funnel; Sentry.
- Accessibility pass; performance budget on a mid-range Android phone.

**Done when:** the launch checklist in the V1 plan doc is complete.
