# API MAP

Base prefix: `/api`

Auth
- POST /api/auth/login — issue auth token/login.

Finance (legacy alias)
- Legacy "finances" aliases for list endpoints (transactions/receipts/spendings); prefer /api/finance/*.

Finance (current)
- GET /api/finance/cashboxes — list cashboxes with basic info.
- GET /api/finance/cashboxes/{cashBoxId}/balance — current balance by cashbox.
- GET /api/finance/receipts — list receipts.
- POST /api/finance/receipts/contract — create receipt linked to contract.
- POST /api/finance/receipts/director-loan — create director loan receipt.
- GET /api/finance/spendings — list spendings.
- POST /api/finance/spendings — create spending.
- POST /api/finance/director-withdrawal — create director withdrawal.
- GET /api/finance/cash-transfers — list cash transfers.
- POST /api/finance/cash-transfers — transfer between cashboxes.

Settings
- /api/settings/contract-statuses — CRUD contract statuses (apiResource).
- /api/settings/cash-boxes — CRUD cashboxes (apiResource).
- /api/settings/companies — CRUD companies (apiResource).
- /api/settings/spending-funds — CRUD spending funds (apiResource).
- /api/settings/spending-items — CRUD spending items (apiResource).
- GET /api/settings/cities — list cities.
- GET /api/settings/cities-districts — list city districts.
- /api/settings/sale-types — CRUD sale types (apiResource).
- GET /api/settings/tenants — list tenants.

Products
- GET /api/products — list products.
- GET /api/products/categories — list categories.
- GET /api/products/subcategories — list subcategories.
- GET /api/products/brands — list brands.

Contracts
- GET /api/contracts — list contracts.

Dev control
- GET /api/dev-control — current dev control flags.
- PATCH /api/dev-control/{id} — update flag.
- POST /api/dev-control/sync-defaults — sync defaults.
