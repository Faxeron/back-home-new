# Structure Guide (modules)

Purpose
- Keep a single structure across modules so the ERP can scale into SaaS.
- Make pages predictable and reusable.

File structure
- Backend domain: `app/Domain/*` (business models/services/repositories).
- Frontend (module-first):
  - `resources/ts/modules/<feature>/`
    - `pages/`        feature pages (logic + UI composition)
    - `components/`   feature UI components
    - `composables/`  feature logic (filters, queries, side effects)
    - `api/`          feature API adapters (no Vue refs)
    - `types/`        feature types
    - `config/`       feature table configs, enums, columns
    - `store/`        feature-local state (rare)
  - `resources/ts/pages/*`       route wrappers (thin; import module pages)
  - `resources/ts/utils/*`       shared formatters/helpers
  - `resources/ts/stores/*`      shared dictionaries/global state

Layering rules
- api: only $api calls and mapping; no component state.
- composables: keep all state and side effects; call api; expose refs + actions.
- components: render UI; no direct $api; receive refs/handlers via props.
- pages (module): connect route params + composables + components.
- pages (route): thin wrappers that import module pages.

Shared (cross-module)
- Put shared formatters, UI patterns, and reusable table configs in `resources/ts/@core/shared/*`.
- Only module-specific items live inside a module; shared items must not be copied between modules.

Import policy (hard rule)
- A module can import only from:
  - its own module files, and
  - `@core/shared/*`, `utils/*`, `stores/*`, `types/*` when they are global.
- Cross-module imports must never target `pages/`, `components/`, or `composables/` of another module.
- Cross-module imports are allowed only from `modules/<feature>/api/*`, `modules/<feature>/types/*`,
  or `modules/<feature>/config/*` (public, stable surface).

Naming consistency
- Keep the same folder layout inside every module: `pages/`, `components/`, `composables/`, `api/`, `types/`, `config/`.
- Use consistent names: `useXxx` for composables, `*.config.ts` for configs, `*.api.ts` for API adapters.

Dictionaries
- Use `resources/ts/stores/dictionaries.ts` for shared lookups.
- Feature lookups live in `resources/ts/composables/*Lookups.ts` if needed.

Micro-standards (checklist)
1) New entity: create module api + composable + UI component + page.
2) Filters live in composables, not in UI tables.
3) Use `useTableLazy`/`useTableInfinite` for tables (no ad-hoc fetch loops).
4) Table columns/labels/formatters go to `modules/<feature>/config/*` (or `@core/shared/*` if reused).
5) Formatters shared across modules live in `resources/ts/@core/shared/formatters/*`.
6) Phone inputs must use `AppPhoneField`.
7) Use `TableTotalLabel` for "Total: N".
8) Routing follows `docs/ROUTING.md` (folder + index for nested pages).
9) Respect tenant/company scope; do not pass ids from the client unless required.

Example: Estimates
- module: `resources/ts/modules/estimates/`
  - api: `api/estimate.api.ts`
  - composables: `composables/useEstimateEditor.ts`, `useEstimateFilters.ts`
  - components: `components/EstimateEditor.vue`, `components/EstimatesTable.vue`
  - pages: `pages/estimates/index.vue`, `pages/estimates/new.vue`, `pages/estimates/\[id]/edit.vue`
- route wrappers: `resources/ts/pages/estimates/*`

Current module map (frontend)
- finance: transactions/receipts/spendings + finance dictionaries (cash boxes, spending funds/items, transaction types)
- settings: common dictionaries (companies, cities, districts, contract statuses, sale types)
- production: operations domain (contracts, measurements, installations)
- estimates
- products

Route wrappers by domain
- finance: `resources/ts/pages/finance/*`
- settings: `resources/ts/pages/settings/*`
- production: `resources/ts/pages/operations/*`
