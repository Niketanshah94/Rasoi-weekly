# Rasoi

Rasoi is a weekly meal planner for households. It plans breakfast, lunch, evening snack, dinner and a late-night craving for every day of the week, shows nutrition and a healthy-day check, builds the week's shopping list, and shares the day's menu or the shopping list on WhatsApp, including with a hired cook. A 7-day free trial leads to a monthly or yearly subscription.

Markets at launch: India, United States, United Kingdom, United Arab Emirates, Singapore.

## Source of truth

- `prototype/index.html` is a working clickable prototype of the whole product: customer app, sign-up, onboarding, paywall, account and the admin panel (open it with `#admin`). Match its screens, copy and behaviour unless `docs/PRODUCT_SPEC.md` says otherwise.
- `docs/PRODUCT_SPEC.md` holds the business rules in words. When the prototype and the spec disagree, the spec wins; flag the difference.
- `docs/BUILD_PLAN.md` is the build order. Work one milestone at a time and do not start the next until its acceptance criteria pass.
- `data/*.json` is the content to seed: dishes, ingredients, plans, countries, pricing, reference lists.
- `data/golden/plan_resolution.json` records what the prototype produces for 192 combinations of plan, country, household and leave-outs: the resolved week, veg pairings, daily health checks and the weekly shopping list. The plan engine must reproduce these. Use them as test fixtures.

## Architecture

One Laravel application serves everything.

- **Customer web app:** Laravel + Inertia.js + Vue 3 (Composition API, `<script setup>`) + Tailwind CSS. Mobile-first, installable (web manifest + service worker), feels like a native app. Served on the main domain.
- **Mobile apps:** a Capacitor project in `/mobile` that loads the customer web app. Native plugins for Google sign-in, push notifications, share sheet, keep-awake and in-app purchases (RevenueCat). No separate mobile UI code: every customer screen is written once in Vue and ships to web, Android and iOS. The Android shell comes early (milestone M3b) so the app can be tested on a phone throughout the build; iOS follows in M9.
- **Admin panel:** Filament, on its own subdomain (`admin.`), separate `staff` guard and staff table, role-based permissions. Web only.
- **API:** `routes/api.php` with Sanctum tokens, used by the mobile shell (native Google sign-in token exchange, push device registration, RevenueCat entitlements) and by payment webhooks. Customer screens use Inertia controllers, not the API.
- **Background work:** Redis queues with Horizon; the Laravel scheduler for trial reminders, pre-debit notices, Sunday plan messages and daily exchange rates.
- **Billing:** India uses Razorpay Subscriptions (UPI AutoPay and cards). Other countries use Polar with exactly two USD products (monthly, yearly). Purchases inside the iOS and Android apps use RevenueCat. All three sit behind one `BillingProvider` interface and feed one subscription state machine. Never trust the client for subscription state; only verified webhooks change it.
- **Messaging:** Laravel Mail for login links, receipts and reminders. WhatsApp through a provider adapter behind a `WhatsAppGateway` interface (provider to be chosen; build a log-only fake first). Push through Capacitor + FCM/APNs.

## Conventions

- Use the latest stable Laravel and PHP versions that Filament supports. Check versions before installing; do not assume.
- Tests with Pest. Every business rule in the spec gets a test. Run the full suite before calling a task done.
- Money: store integer minor units plus an ISO currency code. Never floats for money.
- Times: store UTC; show in the household's time zone.
- IDs: keep the `key` values from `data/*.json` (for example `poha`, `balanced`) as stable slugs alongside numeric IDs.
- Diet labels and allergens are always derived from a dish's ingredients, never typed in by hand.
- Feature flags and provider credentials live in `.env`; keep `.env.example` complete; never commit secrets.
- Payment webhooks: verify signatures, store the raw event, process idempotently in a queued job.
- Copy: plain English, sentence case, short sentences, typographic apostrophes and quotes (’ “ ”). Copy the prototype's wording where it exists.
- Accessibility: real buttons and labels, 44 px minimum tap targets, visible focus, colour never the only signal, works in light and dark mode.
- Prefer small, reviewable commits with clear messages. One milestone per branch; open a pull request when its acceptance criteria pass.

## Things not to do

- Do not invent payment provider API details. Read Razorpay, Polar and RevenueCat documentation, and build against their sandbox or test mode.
- Do not put Google sign-in inside the app's WebView; use the native plugin and exchange the token with the API.
- Do not show beef or pork leave-out options in India; do show them elsewhere.
- Do not let a dish that is not published reach any customer screen, plan or shopping list.

## Stack versions (set in M0)

Laravel 13, PHP 8.3 (pinned as the Composer platform), Inertia 3, Vue 3.5 with TypeScript, Tailwind 4, Filament 5, Pest 4, Sanctum 4, Horizon 5, PostgreSQL 16, Redis 7. Pest 5 needs PHP 8.4; move up together when the hosting and CI images are on 8.4.

- The customer app came from the official Laravel Vue starter kit with only registration and login kept. Its password sign-in (Fortify) is temporary; M3 replaces it with Google and email links.
- The admin panel and Horizon are served only on `ADMIN_DOMAIN` (locally `admin.localhost`), behind the `staff` guard and `staff` table. Only staff with the `owner` role can open Horizon.
- Fonts match the prototype (Figtree, Bricolage Grotesque, Noto Sans Devanagari) and are downloaded at build time by the Vite fonts plugin.
- `CLAUDE.md`, `README.md`, `data/`, `docs/` and `prototype/` are excluded from the front-end formatter: keep them exactly as delivered.

## Commands

| What | Command |
|---|---|
| First-time setup (installs, `.env`, key, migrate, build) | `composer setup` |
| Dev server, queue, logs and Vite together | `composer dev` |
| Customer app | http://localhost:8000 |
| Admin panel | http://admin.localhost:8000 |
| Horizon | http://admin.localhost:8000/horizon |
| Create a staff login | `php artisan make:filament-user` (new staff are editors; set `role` to `owner` for Horizon) |
| Queue worker on its own | `php artisan horizon` |
| Everything CI runs (format, lint, types, Pint, PHPStan, Pest) | `composer ci:check` |
| Tests only | `php artisan test` |
| Fix PHP style | `composer lint` |
| Fix front-end format and lint | `npm run check:fix` |
| Production build | `npm run build` |

Local services: PostgreSQL with databases `rasoi` and `rasoi_test` (user and password `rasoi`), and Redis on the default port. Tests run against `rasoi_test`.

Capacitor commands (sync, run on Android) are added in M3b.
