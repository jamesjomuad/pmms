# PMMS — Precision Mechanical Management System

Internal ERP-style platform for a commercial HVAC contractor. Manages the full construction project lifecycle — from award through closeout — with role-based access, stage tracking, and a generic approval engine.

## Tech Stack

| Layer        | Technology                                          |
| ------------ | --------------------------------------------------- |
| Backend      | Laravel 13, PHP 8.3+                                |
| Frontend     | Vue 3.5, Inertia.js v3                              |
| Styling      | Tailwind CSS v4, shadcn-vue (new-york-v4)           |
| Auth         | Laravel Fortify (session, passkeys/WebAuthn, 2FA)   |
| Database     | PostgreSQL                                          |
| Routes       | Laravel Wayfinder (typed)                           |
| Icons        | Lucide (`@lucide/vue`)                              |
| Activity Log | spatie/laravel-activitylog                          |
| Media        | spatie/laravel-medialibrary                         |
| Testing      | Pest v5                                             |
| Code Quality | PHPStan (Larastan), Pint, ESLint, Prettier, vue-tsc |

## Features

- **Project management** — create, track, and manage HVAC projects through 10 workflow stages
- **Stage-based workflow** — Awarded → Submittals → Shop Drawings → Equipment → Mobilization → Installation → Startup → TAB → Punch List → Closeout
- **Submittals** — create, revise, approve/reject with revision tracking
- **RFIs** — request for information workflow with submit/respond flow
- **Shop drawings** — submit, review, approve/reject cycle
- **Equipment tracking** — with nested supplier quotations, purchase orders, and inspections
- **Punch list** — create and resolve items
- **Change orders** — create, submit, approve/reject
- **Approval engine** — generic polymorphic approval workflow (approval requests + steps)
- **Activity log** — global and project-scoped audit trail
- **Team management** — project-level roles (PM, Field Tech, Estimator, Exec, Admin)
- **Grid/table view toggle** — switch between card grid and table view on project listing

## Prerequisites

- PHP 8.3+
- Node.js 22
- PostgreSQL
- [lerd](https://github.com/anomalyco/lerd) (local development environment)

## Getting Started

```bash
# Clone the repository
git clone <repo-url>
cd pmms

# Run the full setup (installs deps, copies .env, generates key, runs migrations, builds assets)
composer setup
```

### lerd setup

The project is configured via `.lerd.yaml`:

```yaml
domains:
    - pmms
php_version: '8.5'
node_version: '22'
framework: laravel
framework_version: '13'
secured: true
services:
    - mailpit
    - postgres
    - redis
    - soketi:
          preset: soketi
workers:
    - vite
```

Start the environment:

```bash
lerd link        # Generate nginx vhost and SSL certs
lerd up          # Start all services
```

The app will be available at `https://pmms.test`.

## Development

PHP commands run inside the lerd FPM container. Frontend commands run on the host via fnm.

```bash
# Start the dev server (disables timeout, runs Vite + queue worker)
composer dev
```

### Code Quality

```bash
# PHP
composer lint          # Pint --parallel (PSR-12, laravel preset)
composer lint:check    # Pint dry-run
composer types:check   # PHPStan level 7
composer test          # config:clear → lint:check → types:check → artisan test

# Frontend
node node_modules/.bin/vue-tsc --noEmit    # TypeScript check
node node_modules/.bin/eslint resources/   # ESLint
node node_modules/.bin/prettier --check resources/  # Prettier

# Full CI check
composer ci:check      # lint → format → types → tests
```

**Verification order:** lint → typecheck → test. Each step depends on the previous.

## Project Structure

```
├── app/
│   ├── Actions/          # Fortify action classes (registration, password reset)
│   ├── Concerns/         # Shared traits (HasWorkflowStatus, HasApprovals, etc.)
│   ├── Enums/            # PHP enums (ProjectRole, TeamRole, WorkflowStatus)
│   ├── Http/
│   │   ├── Controllers/  # Thin controllers (settings, teams)
│   │   ├── Policies/     # Authorization policies (Project, User, Team)
│   │   └── Requests/     # Form request validation classes
│   ├── Models/           # Eloquent models (Project, Stage, User, Team, etc.)
│   ├── Notifications/    # Notification classes
│   └── Rules/            # Custom validation rules
├── config/
├── database/
│   ├── factories/        # Model factories for testing
│   ├── migrations/       # Database migrations
│   └── seeders/          # Database seeders
├── docs/                 # Architecture docs and ADRs
│   └── adr/              # Architecture Decision Records (001–007)
├── resources/
│   ├── css/              # Tailwind CSS v4 (CSS-first, no config file)
│   └── js/
│       ├── components/   # Vue components
│       │   └── ui/       # shadcn-vue components (generated, do not edit)
│       ├── composables/  # Shared composables (statusHelpers, etc.)
│       ├── layouts/      # Inertia layouts (app, auth, settings)
│       ├── pages/        # Inertia page components
│       │   ├── projects/ # Project pages + sub-modules
│       │   │   ├── change-orders/
│       │   │   ├── equipment/
│       │   │   │   ├── inspections/
│       │   │   │   ├── purchase-orders/
│       │   │   │   └── quotations/
│       │   │   ├── punch-list/
│       │   │   ├── rfis/
│       │   │   ├── shop-drawings/
│       │   │   └── submittals/
│       │   ├── auth/
│       │   ├── settings/
│       │   └── teams/
│       ├── routes/       # Wayfinder typed routes (generated, do not edit)
│       └── types/        # TypeScript type definitions
├── routes/
├── tests/
│   ├── Feature/          # Feature tests (105 tests)
│   └── Unit/             # Unit tests
└── vite.config.ts
```

### Generated Directories

These directories are auto-generated by tooling — do not edit them directly:

- `resources/js/actions/` — Wayfinder actions
- `resources/js/components/ui/` — shadcn-vue components
- `resources/js/routes/` — Wayfinder typed routes
- `resources/js/wayfinder/` — Wayfinder runtime

## Testing

```bash
composer test          # Runs lint → typecheck → Pest test suite
```

Tests use SQLite in-memory, synchronous queue, and array mail driver. Current count: **106 tests, 392 assertions**.

## Architecture

Full architecture documentation lives in `docs/PMMS-ARCHITECTURE.md` — the source of truth for the data model, workflow stages, approval engine, and phased build plan.

Additional docs in `docs/`:

| Document                        | Description                                                           |
| ------------------------------- | --------------------------------------------------------------------- |
| `PMMS-ARCHITECTURE.md`          | Master architecture reference                                         |
| `PMMS-DATA-MODEL.md`            | Table/column/relationship spec                                        |
| `PMMS-WORKFLOW-ARCHITECTURE.md` | Four workflow concerns (State, Transition, Permission, Business Rule) |
| `APPROVAL-ENGINE.md`            | Generic polymorphic approval engine                                   |
| `QUEUE-ARCHITECTURE.md`         | Async processing patterns                                             |
| `SECURITY-ARCHITECTURE.md`      | Authentication, authorization, audit                                  |
| `TESTING-ARCHITECTURE.md`       | Test tiers, conventions, coverage                                     |
| `APP-ARCHITECTURE.md`           | Code structure and domain layout                                      |
| `ROADMAP.md`                    | 10-phase implementation roadmap                                       |
| `adr/`                          | Architecture Decision Records 001–007                                 |

## Contributing

### Code Style

- **PHP**: PSR-12 via Pint (laravel preset)
- **TypeScript/Vue**: ESLint + Prettier (4-space indent, single quotes, semicolons)
- **Type imports**: ESLint enforces `import type { Foo } from '...'` (top-level, not inline)
- **Curly braces**: always required (ESLint `curly: ['error', 'all']`)
- **Tailwind v4**: CSS-first config in `resources/css/app.css` via `@theme inline`, no `tailwind.config.js`
- **Icons**: always import from `@lucide/vue` (not `lucide-vue-next`)

### Before Committing

Always run in order: **lint → typecheck → test**

```bash
composer ci:check
```

### Changelog

Follow [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/) format with SemVer. Entries go in `CHANGELOG.md` under `## [Unreleased]` using imperative mood, grouped under Added/Changed/Fixed/Removed headers.

## License

MIT
