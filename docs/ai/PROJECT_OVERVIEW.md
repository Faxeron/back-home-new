# PROJECT OVERVIEW

## Кратко (RU)
- ERP на Laravel 12 + Vue 3 (Vuexy + PrimeVue), ориентирована на SaaS и мульти‑компании.
- Основные модули: finance, products (catalog), estimates, production (contracts/measurements/installations), settings.
- База данных: основная связь через `legacy_new` (default connection не используется).

## Технологии
- Backend: PHP 8.2+, Laravel 12; Domain/Services/Repositories; FormRequest + Resources; события/джобы.
- Frontend: Vue 3 + Vite + TypeScript; Pinia; Vue Router; Vuexy (Vuetify 3); PrimeVue 4.
- Tooling: ESLint/Stylelint, vue-tsc, Iconify (`npm run build:icons`), MSW (`npm run msw:init`).

## API (обзор)
- Auth: `POST /api/auth/login`.
- Finance: `/api/finance/*` (транзакции, приходы, расходы, кассы, типы транзакций, методы оплат).
- Settings: `/api/settings/*` (общие справочники, включая настройки З/П).
- Catalog: `/api/products/*` (товары, категории, бренды, подкатегории).
- Contracts: `/api/contracts`.

## Frontend структура
- Module‑first: `resources/ts/modules/<feature>/`.
  - `pages/`, `components/`, `composables/`, `api/`, `types/`, `config/`, `store/` (редко).
- Route wrappers: `resources/ts/pages/*` (тонкие файлы для автроутера).
- Shared: `resources/ts/@core/shared/*`, `resources/ts/utils/*`, `resources/ts/stores/*`.

## Модульная карта
- finance: транзакции/приходы/расходы + финансовые справочники.
- settings: общие справочники (companies, cities, districts, contract statuses, sale types, payroll).
- production: contracts, measurements, installations.
- estimates
- products

## Полезные документы
- `docs/STRUCTURE_GUIDE.md` — слои, импорты, модульная структура.
- `docs/UI_STANDARDS.md` — стандарты UI (Vuexy + PrimeVue).
- `docs/CRM_TABLE_STRUCTURE.md` — правила таблиц CRM.
- `docs/project-structure.txt` — структура репозитория.
- `docs/Finance Module.md`, `docs/schema.txt`, `docs/ai/API_MAP.md`.
