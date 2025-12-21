# PROJECT OVERVIEW

Комментарий (RU)
- Краткий обзор модулей и техстека. Детали см. `docs/Finance Module.md` и `docs/schema.txt`.

## Назначение
- ERP-админка на Laravel 12 + Vue 3 (база Vuexy).
- Модули: finance, catalog, CRM/contracts, settings, dev-control.

## Технологии
- Backend: PHP 8.2+, Laravel 12; Domain/Services/Repositories; FormRequest + Resources; события/джобы.
- DB: основное подключение `legacy_new` (default connection не используется).
- Frontend: Vue 3 + Vite + TypeScript; Pinia; Vue Router; PrimeVue 4 + Vuetify 3.
- UI/Media: ApexCharts, Chart.js, Tiptap, mapbox-gl.
- Tooling: ESLint/Stylelint, vue-tsc, Iconify (`npm run build:icons`), MSW (`npm run msw:init`).
- Docker: `compose.yaml` для локального стека.

## API и доменные акценты
- Auth: `POST /api/auth/login`.
- Finance: список транзакций, кассы/балансы, поступления, расходы, переводы, справочники (`/api/finance/*`).
- Settings: contract statuses, cash boxes, companies, spending funds/items, sale types, cities/districts, tenants (`/api/settings/*`).
- Catalog: products + categories/brands/subcategories (`/api/products/*`).
- Contracts: `GET /api/contracts`.
- Dev-control: `/api/dev-control`.
- FinanceService (`App\Services\Finance\FinanceService`) контролирует суммы, балансы и пишет историю касс.

## Данные и фоновые задачи
- Ключевые таблицы: `transactions`, `transaction_types`, `receipts`, `spendings`, `cash_transfers`, `cashbox_history`, `cashboxes`, `payment_methods`, `cashbox_balance_snapshots`.
- Баланс считается по транзакциям и сохраняется в `cashbox_history`; снапшоты пишет `CashBoxBalanceSnapshotJob`.
- Тесты: `tests/Feature/FinanceServiceTest.php` покрывает основные кейсы переводов и балансов.

## Frontend структура
- Исходники в `resources/ts` (api, components, composables, pages, stores, types, navigation, plugins, views, `main.ts`).
- Автоген `.d.ts`: `auto-imports.d.ts`, `components.d.ts`, `typed-router.d.ts`.

## Локальный запуск
- Backend: `composer install`, `.env` из `.env.example`, `php artisan key:generate`, миграции.
- Frontend: `npm install` (или `pnpm install`), затем `npm run build:icons`, `npm run dev`.
- Dev-стек: `composer run dev` (artisan + queue + pail + Vite).
- Tests: `composer test`; фронтенд линт/типы: `npm run lint`, `npm run typecheck`.

## Полезные документы
- `docs/project-structure.txt`
- `docs/Finance Module.md`
- `docs/schema.txt`
- `docs/ai/API_MAP.md`
