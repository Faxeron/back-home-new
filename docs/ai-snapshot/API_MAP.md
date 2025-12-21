# API MAP

## Endpoints
| METHOD | PATH | Controller@method | Request | Resource | Service | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| POST | /api/auth/login | AuthController@login | - | - | - | Auth token issuance |
| GET | /api/finance/cashboxes | Finance\CashBoxController@index | - | inline array | FinanceService.getCashBoxBalance | Returns list with computed balances |
| GET | /api/finance/cashboxes/{cashBoxId}/balance | Finance\CashBoxController@balance | - | - | FinanceService.getCashBoxBalance | Balance only |
| GET | /api/finance/receipts | ReceiptController@index | Request -> ReceiptFilterDTO | ReceiptResource | ReceiptService.paginate | Same controller as legacy list |
| POST | /api/finance/receipts/contract | ReceiptController@storeContract | CreateContractReceiptRequest | ReceiptDTO (array) | FinanceService.createContractReceipt | Requires contract_id |
| POST | /api/finance/receipts/director-loan | ReceiptController@storeDirectorLoan | CreateDirectorLoanReceiptRequest | ReceiptDTO (array) | FinanceService.createDirectorLoanReceipt | No contract |
| GET | /api/finance/spendings | SpendingController@index | Request -> SpendingFilterDTO | SpendingResource | SpendingService.paginate | Same controller as legacy list |
| POST | /api/finance/spendings | SpendingController@store | CreateSpendingRequest | SpendingDTO (array) | FinanceService.createSpending | |
| POST | /api/finance/director-withdrawal | DirectorController@withdrawal | CreateDirectorWithdrawalRequest | SpendingDTO (array) | FinanceService.createDirectorWithdrawal | Uses fond_id=1, spending_item_id=1 |
| GET | /api/finance/cash-transfers | CashTransferController@index | ListCashTransfersRequest | CashTransferResource | CashTransferService.paginate | |
| POST | /api/finance/cash-transfers | CashTransferController@store | CreateCashTransferRequest | CashTransferDTO (array) | FinanceService.transferBetweenCashBoxes | Creates 2 transactions |
| GET | /api/settings/contract-statuses | ContractStatusController@index | apiResource | - | - | Settings catalog |
| POST | /api/settings/contract-statuses | ContractStatusController@store | apiResource | - | - | Settings catalog |
| GET | /api/settings/contract-statuses/{id} | ContractStatusController@show | apiResource | - | - | |
| PUT/PATCH | /api/settings/contract-statuses/{id} | ContractStatusController@update | apiResource | - | - | |
| DELETE | /api/settings/contract-statuses/{id} | ContractStatusController@destroy | apiResource | - | - | |
| apiResource | /api/settings/cash-boxes | CashBoxController | apiResource | - | - | CRUD cash boxes |
| apiResource | /api/settings/companies | CompanyController | apiResource | - | - | CRUD companies |
| apiResource | /api/settings/spending-funds | SpendingFundController | apiResource | - | - | CRUD spending funds |
| apiResource | /api/settings/spending-items | SpendingItemController | apiResource | - | - | CRUD spending items |
| GET | /api/settings/cities | CityController@index | - | - | - | |
| GET | /api/settings/cities-districts | CityDistrictController@index | - | - | - | |
| apiResource | /api/settings/sale-types | SaleTypeController | apiResource | - | - | CRUD sale types |
| GET | /api/settings/tenants | TenantController@index | - | - | - | |
| GET | /api/products | Catalog\ProductController@index | - | - | - | Product listing |
| GET | /api/products/categories | Catalog\ProductCategoryController@index | - | - | - | |
| GET | /api/products/subcategories | Catalog\ProductSubcategoryController@index | - | - | - | |
| GET | /api/products/brands | Catalog\ProductBrandController@index | - | - | - | |
| GET | /api/contracts | ContractController@index | - | - | - | Contracts list |
| GET | /api/dev-control | DevControlController@index | - | - | - | Dev flags |
| PATCH | /api/dev-control/{id} | DevControlController@update | - | - | - | |
| POST | /api/dev-control/sync-defaults | DevControlController@syncDefaults | - | - | - | |

## Дубли и несовпадения
- Префикс `/api/finances` используется только для read-only списков, создание — под `/api/finance`.

## Settings блок
- Все `/api/settings/*` маршруты из `routes/api.php`: contract-statuses, cash-boxes, companies, spending-funds, spending-items, sale-types, cities, cities-districts, tenants.
