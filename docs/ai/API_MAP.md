# API MAP

Base prefix
- `/api`

Public (без auth)
- POST `/api/auth/login`
- GET `/api/estimate/{randomId}`
- GET `/api/estimate/{randomId}mnt`
- GET `/api/public/cities`
- GET `/api/public/products`

Finance (auth:sanctum + tenant.company)
- GET `/api/finance/transactions`
- DELETE `/api/finance/transactions/{transaction}`
- GET `/api/finance/cashboxes`
- GET `/api/finance/cashboxes/{cashBoxId}/balance`
- GET `/api/finance/transaction-types`
- GET `/api/finance/payment-methods`
- GET `/api/finance/funds`
- GET `/api/finance/spending-items`
- GET `/api/finance/counterparties`
- GET `/api/finance/counterparties/{counterparty}`
- GET `/api/finance/receipts`
- POST `/api/finance/receipts/contract`
- POST `/api/finance/receipts/director-loan`
- GET `/api/finance/spendings`
- POST `/api/finance/spendings`
- DELETE `/api/finance/spendings/{spending}`
- POST `/api/finance/director-withdrawal`
- GET `/api/finance/cash-transfers`
- POST `/api/finance/cash-transfers`

Finance legacy aliases
- GET `/api/finances/transactions`
- GET `/api/finances/receipts`
- GET `/api/finances/spendings`

Common
- GET `/api/common/companies`

Dev control
- GET `/api/dev-control`
- PATCH `/api/dev-control/{id}`
- POST `/api/dev-control/sync-defaults`

Settings
- CRUD `/api/settings/contract-statuses`
- CRUD `/api/settings/cash-boxes`
- GET/POST/PATCH/DELETE `/api/settings/cashbox-logos`
- CRUD `/api/settings/companies`
- CRUD `/api/settings/spending-funds`
- CRUD `/api/settings/spending-items`
- GET `/api/settings/users`
- CRUD `/api/settings/payroll-rules`
- GET `/api/settings/payroll-accruals`
- GET/POST/DELETE `/api/settings/payroll-payouts`
- GET/PUT `/api/settings/margin`
- GET `/api/settings/cities`
- GET `/api/settings/cities-districts`
- CRUD `/api/settings/sale-types`
- GET `/api/settings/tenants`
- GET `/api/settings/roles-permissions`
- PATCH `/api/settings/roles-permissions/roles/{role}`
- PATCH `/api/settings/roles-permissions/users/{user}`

Products
- GET `/api/products`
- GET `/api/products/{product}`
- PATCH `/api/products/{product}`
- GET `/api/products/categories`
- GET `/api/products/subcategories`
- GET `/api/products/brands`
- GET `/api/products/kinds`
- GET `/api/products/types`
- GET `/api/products/pricebook/export`
- GET `/api/products/pricebook/template`
- POST `/api/products/pricebook/import`

Estimates
- GET `/api/estimates`
- POST `/api/estimates`
- GET `/api/estimates/{estimate}`
- PATCH `/api/estimates/{estimate}`
- DELETE `/api/estimates/{estimate}`
- POST `/api/estimates/{estimate}/apply-template`
- POST `/api/estimates/{estimate}/items`
- PATCH `/api/estimates/{estimate}/items/{item}`
- POST `/api/estimates/{estimate}/contracts`
- POST `/api/estimates/{estimate}/revoke-public`

Estimate templates
- CRUD `/api/estimate-templates/materials`
- CRUD `/api/estimate-templates/septiks`

Knowledge base
- GET/POST `/api/knowledge/articles`
- GET/PATCH/DELETE `/api/knowledge/articles/{article}`
- GET/POST `/api/knowledge/topics`
- GET/POST `/api/knowledge/tags`
- POST `/api/knowledge/articles/{article}/attachments`
- DELETE `/api/knowledge/attachments/{attachment}`
- GET `/api/knowledge/attachments/{attachment}/download`

Contracts
- GET `/api/contracts`
- GET `/api/contracts/{contract}`
- PATCH `/api/contracts/{contract}`
- PATCH `/api/contracts/{contract}/status`
- GET `/api/contracts/status-history`
- GET `/api/contracts/{contract}/history`
- GET `/api/contracts/{contract}/analysis`
- DELETE `/api/contracts/{contract}`
- GET `/api/contracts/{contract}/documents`
- POST `/api/contracts/{contract}/documents`
- DELETE `/api/contracts/{contract}/documents/{document}`
- GET `/api/contracts/{contract}/documents/{document}/download`
- GET `/api/contracts/{contract}/payroll`
- POST `/api/contracts/{contract}/payroll/manual`
- POST `/api/contracts/{contract}/payroll/recalculate`
- CRUD `/api/contract-templates`
- GET/POST `/api/contract-templates/files`

Installations
- GET `/api/installations`
- PATCH `/api/installations/{contract}`

## REALITY STATUS
- Реально реализовано: все маршруты перечислены в `routes/api.php` и совпадают с этим списком.
- Легаси: `/api/finances/*` алиасы для старых клиентов.
- Не сделано: публичные `/api/public/companies`, `/api/public/products/{slug}`, `/api/public/leads` (в планах API_MASTER_PLAN).
