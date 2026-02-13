# API MAP

Base prefix
- `/api`

Public (без auth)
- GET `/api/estimate/{randomId}`
- GET `/api/estimate/{randomId}mnt`
- GET `/api/public/cities`
- GET `/api/public/companies`
- GET `/api/public/catalog/tree`
- GET `/api/public/catalog/categories/{slug}`
- GET `/api/public/catalog/subcategories/{slug}`
- GET `/api/public/catalog/brands/{slug}`
- GET `/api/public/products`
- GET `/api/public/products/{slug}`
- POST `/api/public/leads`

Auth (SPA cookie session, Sanctum stateful)
- GET `/sanctum/csrf-cookie`
- POST `/login`
- POST `/logout`
- POST `/logout-all`
- POST `/api/auth/login` (legacy alias, kept for compatibility)
- POST `/api/auth/logout` (legacy alias, kept for compatibility)
- POST `/api/auth/logout-all` (legacy alias, kept for compatibility)
- GET `/api/user`

Public catalog rules (для сайта)
- Для всех catalog/products endpoints обязателен контекст `city` или `company_id` (иначе 400).
- `tenant_id=1` фиксирован для public API.
- Товары: `products.is_visible=1`, `products.archived_at IS NULL`.
- Цены: только `product_company_prices` с `is_active=1` и не-NULL `price_sale` или `price` (иначе товар не попадает в каталог).
- Отладка: `?no_cache=1` отключает серверный кэш (если endpoint поддерживает).

Finance (auth:sanctum + tenant.company)
- GET `/api/finance/transactions`
- GET `/api/finance/transactions/summary`
- GET `/api/finance/transactions/cashflow-series`
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

Dashboards (auth:sanctum + tenant.company)
- GET `/api/dashboards/employee/summary`

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
- Реально реализовано: маршруты перечислены в `routes/api.php` и отражены в этом списке.
- Легаси: `/api/finances/*` алиасы для старых клиентов.
- Не сделано: отсутствует отдельный публичный API для компаний вне tenant=1 (не требуется сейчас).
