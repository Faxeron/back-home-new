# API MAP

Комментарий (RU)
- Карта сверена с `routes/api.php`. При добавлении/изменении маршрутов обновляй список ниже.
- Базовый префикс: `/api`.

Auth
- POST /api/auth/login — вход и выдача токена.

Finance (current)
- GET /api/finance/transactions — список транзакций.
- GET /api/finance/cashboxes — список касс с балансом.
- GET /api/finance/cashboxes/{cashBoxId}/balance — баланс по кассе.
- GET /api/finance/transaction-types — справочник типов транзакций.
- GET /api/finance/payment-methods — способы оплаты.
- GET /api/finance/funds — фонды расходов.
- GET /api/finance/spending-items — статьи расходов.
- GET /api/finance/counterparties — контрагенты.
- GET /api/finance/receipts — список поступлений.
- POST /api/finance/receipts/contract — поступление по договору.
- POST /api/finance/receipts/director-loan — заем директора.
- GET /api/finance/spendings — список расходов.
- POST /api/finance/spendings — создать расход.
- POST /api/finance/director-withdrawal — вывод средств директором.
- GET /api/finance/cash-transfers — список переводов между кассами.
- POST /api/finance/cash-transfers — перевод между кассами.

Finance (legacy aliases)
- GET /api/finances/transactions — legacy alias списка транзакций.
- GET /api/finances/receipts — legacy alias списка поступлений.
- GET /api/finances/spendings — legacy alias списка расходов.

Settings
- /api/settings/contract-statuses — CRUD статусов договоров.
- /api/settings/cash-boxes — CRUD касс.
- /api/settings/companies — CRUD компаний.
- /api/settings/spending-funds — CRUD фондов расходов.
- /api/settings/spending-items — CRUD статей расходов.
- /api/settings/sale-types — CRUD типов продаж.
- GET /api/settings/cities — список городов.
- GET /api/settings/cities-districts — список районов.
- GET /api/settings/tenants — список тенантов.

Common
- GET /api/common/companies — lookup компаний.

Products
- GET /api/products — список товаров.
- GET /api/products/categories — список категорий.
- GET /api/products/subcategories — список подкатегорий.
- GET /api/products/brands — список брендов.

Estimates
- POST /api/estimates/{estimate}/apply-template
- PATCH /api/estimates/{estimate}/items/{item}

Contracts
- GET /api/contracts — список договоров.

Dev control
- GET /api/dev-control — статусы модулей.
- PATCH /api/dev-control/{id} — обновление статуса.
- POST /api/dev-control/sync-defaults — синхронизация дефолтов.
