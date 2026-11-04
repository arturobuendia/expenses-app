# AGENTS.md

Laravel 12 + Inertia 2 + Vue 3 (TypeScript) personal finance app. UI copy is Spanish; code identifiers are English.

## Commands

- `composer setup` — install PHP deps, create `.env`, key, migrate, `npm install`, build.
- `composer dev` — `artisan serve` + queue listener + Vite.
- `composer dev:ssr` — same plus `artisan pail` and the Inertia SSR server.
- `composer ci:check` — the full local gate: ESLint, Prettier, `vue-tsc`, Pest.
- `composer test` — `config:clear` + Pint check + `artisan test` only (no frontend checks).
- Single test: `php artisan test --filter=DashboardTest` or `vendor/bin/pest tests/Feature/DashboardTest.php`.
- Format/fix: `composer lint` (Pint, Laravel preset) and `npm run format` (Prettier). Use the `:check` variants to verify without writing.

Tests run on SQLite `:memory:` with `RefreshDatabase` applied to every `tests/Feature` test (`tests/Pest.php`). No external services are required.

## Architecture

- Controllers render Inertia pages by name; page files must live at `resources/js/pages/<Name>.vue` (e.g. `DashboardController` renders `Dashboard/Index` → `resources/js/pages/Dashboard/Index.vue`).
- Domain tables are independent: `expenses` and `incomes` (both reference `categories`), `loans`, `subscriptions`, `vaults`. There are no aggregates or shared transaction table.
- Finance records are **global, not per-user**: no `user_id`/tenant scoping exists anywhere. `auth` middleware only gates access; all authenticated users see the same data. Do not assume ownership scoping when adding queries.
- `Loan` is money lent out (`debtor_name`), `is_paid` marks repayment. `Vault` is a capital account; the dashboard computes monthly yield as `balance * annual_yield_rate / 100 / 12`, capped at `yield_cap`.
- `Category` is typed `income | expense`; use the `active()` and `ofType()` scopes instead of raw `where` clauses.
- Money is `decimal(12,2)` with `decimal:2` casts, so amounts reach Vue as strings. Types allow `number | string`; format via `useFormatter()` (es-MX / MXN).
- WhatsApp ingestion is **unfinished**: `Api\WhaasWebhookController`, `AiCategorizationService`, `FinanceManagerService`, and `WhaasService` exist, but no route is registered (`bootstrap/app.php` only loads web/console/health, and there is no `routes/api.php`). Adding a route also requires webhook authentication, which is still a `TODO`.
- External keys live in `config/services.php`: `OPENAI_API_KEY`, `WHAAS_API_URL`, `WHAAS_API_KEY`.

## Frontend conventions

- Generated Wayfinder code (`resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder`) is gitignored and produced by the Vite plugin during `npm run dev`/`build`. Never edit it; run `php artisan wayfinder:generate` if it is missing after a fresh clone.
- Existing pages hardcode URLs (`form.post('/expenses')`) rather than importing generated helpers. Follow the surrounding style unless you are deliberately migrating a page.
- shadcn-vue components in `resources/js/components/ui/**` are excluded from ESLint; `resources/js/pages/**` are not. Keep the `@/` alias (`resources/js/*`) and separate `import type` statements (enforced rule).
- Tailwind is v4 CSS-first via `resources/css/app.css`; there is no `tailwind.config.js` despite `components.json` referencing one.
- Indentation is 4 spaces, LF, single quotes, semicolons. Prettier sorts Tailwind classes using `resources/css/app.css`; do not reorder classes by hand.

## Gotchas

- `config/inertia.php` enables SSR, but `npm run build` and `composer dev` do not start the SSR server; pages silently fall back to client rendering. Use `composer dev:ssr` when validating SSR output.
- `database/seeders/DatabaseSeeder.php` is not idempotent (`User::create` with a hardcoded account) and seeds Spanish demo data. Use `php artisan migrate:fresh --seed`.
- Timezone is `America/Mexico_City`; `APP_LOCALE` is `en` while product copy stays Spanish.
- `AppServiceProvider` blocks destructive DB commands in production and enforces stronger password rules there.
- `resources/js/pages/Dashboard.vue` is an unused starter leftover; the live dashboard is `pages/Dashboard/Index.vue`.
