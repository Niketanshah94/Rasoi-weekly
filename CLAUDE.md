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

- The customer app came from the official Laravel Vue starter kit with only registration and login kept. Since M3 customers sign in with Google or an email link; the starter kit's password sign-in stays behind `PASSWORD_LOGIN` (off by default) only until Google and an email provider are set up on the live server.
- The admin panel and Horizon are served only on `ADMIN_DOMAIN` (locally `admin.localhost`), behind the `staff` guard and `staff` table. Only staff with the `owner` role can open Horizon.
- Fonts match the prototype (Figtree, Bricolage Grotesque, Noto Sans Devanagari) and are downloaded at build time by the Vite fonts plugin.
- `CLAUDE.md`, `README.md`, `data/`, `docs/` and `prototype/` are excluded from the front-end formatter: keep them exactly as delivered.

## Content model (set in M1)

- Tables: `countries`, `cuisines`, `ingredients`, `dishes` (+ `dish_country`, `dish_ingredients`), `plan_templates` (+ `plan_template_country`, `plan_template_items`), `pricing_settings`, `prices`, `exchange_rates`. Every content row keeps its `key` (or country/cuisine `code`) and a `position` holding its order in `data/*.json`.
- Fixed lists are enums in `app/Enums`: `Meal`, `Mood`, `Weekday`, `Allergen`, `Aisle`, `Diet`, `DishStatus`, `PriceMarket`, `BillingInterval`.
- Diet and allergens are never stored: `Dish::diet()` and `Dish::allergens()` derive them from the ingredients. Allergens come out in recipe order, which is how the prototype lists them and how `derived_for_reference` records them. The data's `is_meat_or_fish` and `is_egg` flags are not stored either; a test checks the aisle rule agrees with them.
- A dish-ingredient quantity is in the ingredient's own unit; the seeder refuses data where they differ. `name_hi` is optional: international dishes and ingredients have none.
- `Dish::published()` is the only way dishes should reach customers. Plan slots restrict deletes, so a dish used in a plan cannot be deleted.
- Prices are integer minor units (`Price::for()`); the yearly saving is calculated (`Price::yearlySavingPercent()`), not stored. `plan_templates.dish_country_id` is null for "own country", otherwise the country dishes come from (the Indian plan abroad).
- `ContentSeeder` runs one seeder per data file. It only adds rows whose key is missing and never overwrites, so it is safe on a live database where staff have edited content. `DatabaseSeeder` creates the `test@example.com` user only in the local environment.

## Admin panel (set in M2)

- Filament resources: Dishes, Ingredients, Weekly plans (`/plans`), Countries (read only), Activity; plus the Pricing page. Content sits under "Content", pricing and countries under "Settings".
- Roles come from `StaffRole`: owners do everything; editors manage dishes, ingredients and plans; support staff only look. Only owners change prices. Policies live in `app/Policies`; the role checks are `StaffRole::canEditContent()` and `canEditPricing()`.
- Every admin change writes one plain-English line to the activity log (`spatie/laravel-activitylog` 4.x, log name `admin`) through `App\Filament\Support\AdminActivity::record()`, with the staff member as causer. Version 5 of the package needs PHP 8.4.
- The healthy-day check (spec §6) is plain PHP in `app/Planning` (`Nutrition`, `HealthyDay`, `HealthCheck`) so the plan engine can reuse it. Fat and carb shares are rounded to whole percentages before comparing, as the prototype does.
- New dishes and ingredients get a `key` from their English name and go last in order (`HasKeyFromName`). An ingredient's unit is locked while recipes use it. `Dish::dietOf()` and `allergensOf()` power the admin's live preview.
- The plan editor offers live dishes for the meal from the plan's countries, or from India for the Indian plan abroad (`PlanTemplate::dishPool()`).

## Sign-in (set in M3)

- Pages: `/register` (country, Google, "Email me an activation link") and `/login` (Google, "Email me a login link") are Fortify's views; the link and Google routes are in `routes/auth.php`. Logging in with an unknown email redirects to `/register?email=…`; signing up with a known email sends a login link instead.
- Login links (`LoginLink`, `login_links` table) are signed URLs valid for 15 minutes and claimed once with an atomic update. Opening one shows a confirm button that posts back to the same signed URL, so email scanners that prefetch links can't use them up. Links are rate limited (`login-link` limiter) and sent as a queued `LoginLinkMail`.
- `App\Auth\CustomerSignIn` finds or creates accounts for both methods, marks the email verified, and links a Google ID to an existing account with the same email. New accounts have no password and an empty name until onboarding.
- Google on the web uses Socialite and only shows once `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` are set. The mobile apps call `POST /api/auth/google` with the native sign-in's ID token; `GoogleIdTokenVerifier` checks it against Google's published keys (cached an hour), the issuer, the audience (web, Android or iOS client ID), expiry and a verified email. It returns a Sanctum token and starts a web session; a new account without a country gets a 422 with `needs_country`.
- `PASSWORD_LOGIN=true` turns the starter kit's password sign-up, log-in, reset and Security settings back on; `RequirePasswordLogin` returns 404 for those routes when it is off. Tests run with it on, and switch it off where they check the product's sign-in.
- The account page edits the name only; the email is shown, not edited. Deleting an account needs no password (customers have none) and removes its API tokens.

## Households (set in M4)

- `households` holds country, who cooks, people (1 to 12), non-veg eaters, whether the others eat eggs, leave-outs, plan style and cook details; `household_user` links members with an `owner` or `member` role (one household per person for now). `User::household()` queries it fresh each call.
- `DietMode::for()` and `LeaveOut` (dairy, gluten, nuts, beef, pork; beef and pork only outside India; nuts covers tree nuts and peanuts) implement spec §3, using the same keys as the golden fixtures.
- Onboarding (`/onboarding`) follows the prototype's steps and saves everything at the end; `EnsureHouseholdIsSetUp` (alias `household`) sends anyone without a household there. Household settings (`/settings/household`) edit the same fields through the same `HouseholdRequest`, including the country. Cook details are dropped when the household cooks for itself.
- The plan-style step shows each template's Monday breakfast, lunch and dinner as written; M5 swaps in the resolved week.
- Deleting an account deletes a household nobody else is in.
- Tests: `Tests\TestCase` seeds `ContentSeeder` once per run when `RefreshDatabase` migrates, so content is always there and tests don't seed it themselves.

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
| Load content from `data/*.json` (safe to re-run; adds only what is missing) | `php artisan db:seed --class=ContentSeeder` |
| Fix PHP style | `composer lint` |
| Fix front-end format and lint | `npm run check:fix` |
| Production build | `npm run build` |
| Serve to your network for the phone (build, then serve on 0.0.0.0:8000) | `composer share` |
| Android test app and tunnel setup | see `mobile/README.md` |
| Host on an Ubuntu server (first setup, updates, domain) | see `deploy/README.md` |

Local services: PostgreSQL with databases `rasoi` and `rasoi_test` (user and password `rasoi`), and Redis on the default port. Tests run against `rasoi_test`.

The Android shell in `mobile/` was brought forward from M3b at the owner’s request: GitHub Actions builds a debug APK on every change under `mobile/` and publishes it as the `android-dev` pre-release. The app opens the server in the `RASOI_SERVER_URL` repository variable, falling back to a connect screen that asks for the address. The owner hosts on a Contabo Ubuntu 24.04 server set up with `deploy/provision.sh` (nginx, PHP-FPM as the `rasoi` user, PostgreSQL, Redis, Horizon under Supervisor, Let’s Encrypt), using an sslip.io name until a domain is connected. Native Google sign-in and App Links remain in M3b.
