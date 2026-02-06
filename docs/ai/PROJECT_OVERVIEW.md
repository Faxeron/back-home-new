# PROJECT OVERVIEW

## Кратко (RU)
- ERP на Laravel 12 + Vue 3 (Vuexy/Vuetify + PrimeVue), целится в SaaS и multi-tenant модель.
- Доменные модули: finance, products (catalog), estimates (сметы), production (contracts/installations), settings, knowledge base, ACL/roles.
- Публичный API: отдельный модуль `app/Modules/PublicApi` (cities/companies/products/leads).
- Основная БД: доменные данные живут в connection `legacy_new`; default connection используется только для техслужебных вещей.

## Технологии
- Backend: PHP 8.2+, Laravel 12; Domain/Services/Repositories; FormRequest + Resources; jobs/listeners.
- Frontend: Vue 3 + Vite + TypeScript; Pinia; Vue Router (auto-router); Vuexy (Vuetify 3); PrimeVue 4.
- Tooling: ESLint/Stylelint, vue-tsc, MSW.

## Pricing (как сейчас)
- Операционные цены (`price`, `price_sale`, `price_delivery`, `montaj`, `montaj_sebest`) читаются из `product_company_prices` через `PriceResolverService`.
- В `products` остаются закупочные/вендорные поля (`price_vendor`, `price_vendor_min`, `price_zakup`) и legacy-операционные цены (пока еще пишутся).
- Импорт прайса и редактирование товара пишут в `products`, затем синхронизируют `product_company_prices`.

## Snapshot документов
- Сметы: источник истины — `estimate_items` (price/total фиксируются в момент создания позиции).
- Договоры: `contract_items` создаются из `estimate_items` и являются слепком цены на момент создания договора.
- `estimates.data` — legacy-кеш; используется только как fallback в анализе договора.

## Tenant/Company модель
- Большинство таблиц имеет `tenant_id` + `company_id`.
- Контекст берется из пользователя (`tenant_id`, `default_company_id`/`company_id`).
- Проверка членства: `user_company` + `EnsureCompanyContext`.

## API (обзор)
- Auth: `POST /api/auth/login`.
- Finance: `/api/finance/*`.
- Catalog/Products: `/api/products/*` + `/api/products/pricebook/*`.
- Estimates: `/api/estimates/*`, `/api/estimate-templates/*`.
- Contracts: `/api/contracts/*`, `/api/contract-templates/*`.
- Knowledge base: `/api/knowledge/*`.
- Public API: `/api/public/cities`, `/api/public/companies`, `/api/public/products`, `/api/public/products/{slug}`, `/api/public/leads`.

## Frontend структура
- Module-first: `resources/ts/modules/<feature>/` (pages/components/composables/api/types/config).
- Route wrappers: `resources/ts/pages/*`.
- Shared: `resources/ts/@core/shared/*`, `resources/ts/utils/*`, `resources/ts/stores/*`.

## REALITY STATUS
- Реально реализовано: finance, catalog, estimates, contracts/installations, payroll (rules/accruals/payouts), knowledge base, public API (cities/companies/products/leads), pricebook import/export.
- Легаси: `estimates.data` как кеш, legacy price-поля в `products`.
- Не сделано: полноценный cutover цен на `product_company_prices` во всех модулях + контроль покрытия для public.
