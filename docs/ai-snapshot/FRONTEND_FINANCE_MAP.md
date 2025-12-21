# FRONTEND FINANCE MAP

- Роуты/страницы:
  - /finance/transactions — страница `resources/ts/pages/finance/transactions.vue` на `BaseDataTable` + `useTableLazy`, грузит `/api/finance/transactions` с include cashBox/counterparty/contract/transactionType/paymentMethod.
  - Навигация (`resources/ts/navigation/vertical/apps-and-pages.ts`) содержит пункты Finance: Transactions, Receipts, Spendings (последние два пока не реализованы как страницы).
  - Settings блок: страницы в `resources/ts/pages/settings/*` для cash-boxes, companies, spending-funds, spending-items, sale-types, cities, districts (используют `/api/settings/*`).

- API-клиенты (resources/ts/api/finance/*):
  - transactionsListEndpoint -> `finance/transactions` (list).
  - receiptsListEndpoint -> `finance/receipts` (list).
  - spendingsListEndpoint -> `finance/spendings` (list).

- Конфиги таблиц/фильтров:
  - `resources/ts/config/tables/transactions.ts` — описывает колонки, фильтры, include-поля; используется в `useTableLazy`.
  - `useTableLazy` (composable) + `BaseDataTable` — общая обёртка для ленивой загрузки таблиц (пагинация, фильтры, сортировка).

- Справочники (Pinia):
  - `resources/ts/stores/dictionaries.ts` — грузит cashBoxes (`/api/finance/cashboxes`), transactionTypes (`/api/finance/transaction-types`), paymentMethods (`/api/finance/payment-methods`), spending funds/items (`/api/finance/funds`, `/api/finance/spending-items`), saleTypes (`/api/crm/sale-types`), companies (`/api/common/companies`), counterparties (`/api/finance/counterparties`). Некоторые эндпоинты отсутствуют в routes/api.php (payment-methods, transaction-types, funds, counterparties, common/companies) — риск 404.

- Общие компоненты:
  - `BaseDataTable.vue` — реиспользуемая таблица с фильтрами/сортировкой для finance data.
  - Vuetify VDataTableServer используется на settings страницах (cash-boxes и т.д.) для CRUD списков.
