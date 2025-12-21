# PROJECT OVERVIEW

## Purpose
- ERP-style admin built on Laravel 12 back end with Vue 3 + Vite front end (Vuexy base).
- Supports finance, catalog, contracts, and settings modules for multi-tenant companies.

## Tech Stack
- Backend: PHP 8.2+, Laravel 12, Domain/Services/Repositories layers, API resources + FormRequests, jobs/events for finance logging.
- Frontend: Vite + TypeScript + Vue 3 with Pinia and Vue Router; UI kits PrimeVue 4 and Vuetify 3; charts via ApexCharts/Chart.js; editors Tiptap; mapbox-gl for maps.
- Tooling: ESLint/Stylelint, vue-tsc, MSW for API mocking, Iconify pipeline (`pnpm build:icons` + `msw:init`), Docker Compose provided via `compose.yaml`.

## API and Domain Highlights
- Auth: `POST /api/auth/login`.
- Finance: cashboxes + balances, receipts (contract/director loan), spendings, director withdrawal, cash transfers; transaction feed under `/api/finance/*` (legacy "finances" alias still responds).
- Settings: contract statuses, cash boxes, companies, spending funds/items, sale types, cities/districts, tenants (`/api/settings/*`).
- Catalog: products, categories, subcategories, brands (`/api/products/*`).
- Contracts: `/api/contracts` listing.
- Finance service (`App\\Services\\Finance\\FinanceService`) enforces positive sums, tenant/company consistency, balance checks; writes cashbox history and emits `FinancialActionLogged`.

## Data and Background Work
- Key tables: `transactions`, `transaction_types`, `receipts`, `spendings`, `cash_transfers`, `cashbox_history`, `cash_boxes`, `payment_methods`, `cashbox_balance_snapshots`.
- Snapshot job `CashBoxBalanceSnapshotJob` records daily balances; history is written per transaction for auditability.
- Feature tests cover finance flows (`tests/Feature/FinanceServiceTest.php`).

## Frontend App Structure
- Source lives in `resources/ts` (api clients, components, navigation, pages, views, plugins, types, entrypoint `main.ts`).
- Auto-imported components/types are tracked in generated `.d.ts` files (`auto-imports.d.ts`, `components.d.ts`, `typed-router.d.ts`).

## Local Setup
- Backend: `composer install`, copy `.env` from `.env.example`, `php artisan key:generate`, run migrations.
- Frontend: `pnpm install` (or npm), `pnpm build:icons`, `pnpm dev` for Vite, `pnpm build` for production.
- Combined dev stack: `composer run dev` launches artisan server, queue listener, pail logs, and Vite in parallel.
- Tests: `composer test`; frontend lint/type check via `pnpm lint` and `pnpm typecheck`.

## Useful Docs
- `docs/project-structure.txt` for directory map.
- `docs/Finance Module.md` for finance rules and flows.
- `docs/schema.txt` for database reference.
- PrimeVue notes in `docs/primevue/` and related txt files.
