## PMMS — Precision Mechanical Management System

Internal ERP-style platform for a commercial HVAC contractor. Laravel 13 + Vue 3 (Inertia.js v3) + PostgreSQL. Full architecture in `docs/PMMS-ARCHITECTURE.md`.

### Stack

- **Backend**: Laravel 13, PHP 8.3+, Sanctum SPA auth
- **Frontend**: Vue 3, Inertia.js v3, Tailwind CSS v4 (CSS-first, no `tailwind.config.js`), shadcn-vue (new-york-v4), Lucide icons (`@lucide/vue`), Wayfinder typed routes
- **Database**: PostgreSQL (via lerd `postgres` service)
- **Services**: mailpit, postgres, redis, soketi
- **Workers**: queue, vite (host worker for HMR)

### Commands

PHP commands run inside the lerd FPM container via `exec`. Frontend commands run on the host via fnm (`.node-version` = 22).

```
# PHP quality (inside container)
composer lint          # Pint --parallel (PSR-12, laravel preset)
composer lint:check    # Pint --parallel --test (dry-run)
composer types:check   # phpstan analyse (level 7)
composer test          # config:clear -> lint:check -> types:check -> artisan test

# Frontend quality (on host)
node node_modules/.bin/vue-tsc --noEmit    # TypeScript check
node node_modules/.bin/eslint resources/   # ESLint
node node_modules/.bin/prettier --check resources/  # Prettier

# Full CI check
composer ci:check      # npm lint:check -> format:check -> types:check -> Pest

# Dev server
composer dev           # artisan dev (disables timeout)
```

### Verification order

Always run: **lint -> typecheck -> test**. Each step depends on the previous.

- `composer lint:check` (Pint) — PHP style
- `composer types:check` (PHPStan level 7) — PHP static analysis
- `vue-tsc --noEmit` — TypeScript/Vue type check
- `eslint resources/js/` — JS/Vue lint
- `composer test` (Pest) — runs all checks then tests

### Conventions

- **Icon imports**: always `from '@lucide/vue'` — NOT `lucide-vue-next`
- **Type imports**: ESLint enforces `import type { Foo } from '...'` (top-level, not inline). See `@typescript-eslint/consistent-type-imports`
- **Import order**: external packages alphabetically (`@inertiajs` before `@lucide`), then `vue`, then internal `@/` aliases. ESLint enforces this
- **Curly braces**: always required — `curly: ['error', 'all']` in ESLint
- **Prettier**: 4-space indent, single quotes, semicolons on (`.prettierrc` `semi: true`), Tailwind class sorting via `prettier-plugin-tailwindcss`. YAML overrides use 2-space indent
- **Tailwind v4**: no `tailwind.config.js`. Theme tokens live in `resources/css/app.css` via `@theme inline` and HSL CSS variables. Dark mode uses `.dark` class (not media query). `@source` directives in `app.css` must include vendor view paths
- **shadcn-vue components**: in `resources/js/components/ui/`. Use `cn()` from `@/lib/utils` for class merging (clsx + tailwind-merge). These directories are generated — do not hand-edit
- **Generated directories** (ESLint ignored, do not edit): `resources/js/actions/`, `resources/js/components/ui/*`, `resources/js/routes/`, `resources/js/wayfinder/`
- **Wayfinder**: typed route helpers generated in `resources/js/routes/`. Import route functions from `@/routes` (e.g. `import { login } from '@/routes'`)
- **Inertia layouts**: selected in `resources/js/app.ts` by page name. Welcome = null (standalone), default = AppLayout (sidebar), auth/ = AuthLayout, settings/ and teams/ = [AppLayout, SettingsLayout]
- **Pages**: Inertia page components live in `resources/js/pages/`. The homepage is `Welcome.vue` (no layout wrapper, full-page ERP landing)
- **Architecture docs**: `docs/PMMS-ARCHITECTURE.md` is the source of truth for data model, workflow stages, approval engine, and phased build plan. Reference it when building new modules

### Troubleshooting

- **Vite entry point 504 / page hangs loading**: The nginx config has a stale WSL IP for the Vite proxy (`/@lerd-vite/`). Lerd proxies Vite assets through the site's own domain at `/@lerd-vite/`, so `@vite` generates URLs like `https://pmms.test/@lerd-vite/resources/js/app.ts`. If the WSL IP changes (common after restart), nginx still points at the old IP and Vite requests time out. Fix: `lerd link` regenerates the nginx vhost with the current IP, then `lerd worker start vite` starts the dev server and writes the proxy block. Check with `curl -skI https://pmms.test/@lerd-vite/resources/js/app.ts` — should return 200 OK.

### CHANGELOG

Follow Changelog 1.1.0 format, SemVer, entries in imperative mood. Group under Added/Changed/Fixed/Removed headers.
