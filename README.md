# Rasoi: handoff kit for Claude Code

Everything Claude Code needs to build the real Rasoi platform: the working prototype, the product rules, the build plan and the seed data.

## What's inside

| Path | What it is |
|---|---|
| `CLAUDE.md` | Standing instructions for Claude Code: product, architecture, conventions, what not to do |
| `docs/PRODUCT_SPEC.md` | Every business rule from the prototype, written for developers |
| `docs/BUILD_PLAN.md` | Eleven milestones (M0 to M10, plus M3b for an early Android build), each with acceptance criteria and a starter prompt |
| `prototype/index.html` | The clickable prototype. Open it in a browser; add `#admin` for the admin panel |
| `data/*.json` | Seed data: 187 dishes, 147 ingredients, 13 weekly plans, 5 countries, pricing, reference lists |
| `data/golden/plan_resolution.json` | 192 test cases recording exactly what the prototype produces, so the Laravel code can be checked against it |

## How to start

1. **On a computer, install Claude Code** (command line or desktop app). Setup guide: https://docs.claude.com/en/docs/claude-code/overview
2. **Install a Laravel environment:** Git, PHP, Composer, Node.js, MySQL or PostgreSQL, and Redis. Laravel Herd or Laravel Sail (Docker) sets most of this up. For the mobile apps later you'll also need Xcode on a Mac and Android Studio.
3. **Create a private GitHub repository**, for example `rasoi`, unzip this kit into it and commit.
4. **Open the folder in Claude Code** and paste the M0 starter prompt from `docs/BUILD_PLAN.md`:
   > Read CLAUDE.md and docs/BUILD_PLAN.md. Plan milestone M0: check current Laravel, Inertia, Vue, Filament and Pest versions, then scaffold the project. Show me the plan before you run anything.
5. **Go one milestone at a time.** Read its plan, let it build, check that the tests pass, try it in the browser, then merge the pull request before starting the next one.

## Accounts and keys you'll need

| By milestone | What |
|---|---|
| M3 | Google Cloud OAuth client (web, iOS, Android); an email sending service (Amazon SES, Postmark or Resend) |
| M7 | Razorpay account with Subscriptions enabled (test mode first); Polar account with two products, monthly and yearly |
| M8 | WhatsApp Business API provider account with Meta business verification; Firebase project for push |
| M3b | A staging server the Android app can load (or a phone on the same Wi-Fi as your computer); an Android phone with developer mode on |
| M9 | Apple Developer Program; Google Play Console; RevenueCat account with matching store products |
| M10 | Hosting (Laravel Cloud or Forge), domain and admin subdomain, Sentry, PostHog |

## Tips

- Keep `CLAUDE.md` up to date when you make a decision; Claude Code reads it as standing instructions.
- When something looks different from the prototype, point Claude Code at the screen in `prototype/index.html` and say what should match.
- The plan engine is the riskiest logic. Don't move past M5 until all 192 golden cases pass.
- You can check in on a running Claude Code session from the Claude mobile app.
