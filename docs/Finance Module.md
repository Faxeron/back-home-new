# Finance Module (current)

Комментарий для разработчика (RU)
- Здесь описана текущая схема и API. Канон — `cashboxes`/`cashbox_id`; при изменениях legacy-алиасов синхронизируй FormRequest и ресурсы.
- В API живут legacy-алиасы `/api/finances/*`; фронт/бэк используют `cashbox_id`.
- Типы транзакций берутся из `transaction_types.sign`, поэтому enum в коде — только вспомогательный.
- Сервис `FinanceService` отвечает за бизнес-логику и балансы; сервисы `*Service` — за списки/CRUD.

- Core tables: `transactions`, `receipts`, `spendings`, `cash_transfers`, `cashbox_history`, `cashboxes`, `cashbox_company`, `cashbox_balance_snapshots`, `payment_methods`, `transaction_types`.
- Naming: canonical is `cashbox_id` + `cashboxes`.
- Transaction types: `INCOME` +1, `OUTCOME` -1, `TRANSFER_IN` +1, `TRANSFER_OUT` -1, `ADVANCE` +1, `REFUND` +1, `DIRECTOR_LOAN` +1, `DIRECTOR_WITHDRAWAL` -1 (sign is read from `transaction_types.sign`).
- Services:
  - `App\Services\Finance\FinanceService` handles contract receipts, director loan receipts, spendings, director withdrawals, and cash transfers; it runs inside `DB::connection('legacy_new')->transaction()`, writes `cashbox_history`, and prevents negative balances for negative-sign transactions.
  - `TransactionService`, `ReceiptService`, `SpendingService`, `CashTransferService` handle list/pagination + CRUD helpers with filters/includes.
- DTOs/Resources: Finance flows return `ReceiptDTO`, `SpendingDTO`, `CashTransferDTO`, `TransactionDTO`; list endpoints return `ReceiptResource`, `SpendingResource`, `TransactionResource`, `CashTransferResource`.
- Snapshot job: `CashBoxBalanceSnapshotJob` stores balances in `cashbox_balance_snapshots` (by `cashbox_id`).

API (prefix `/api/finance`)
- GET `transactions` (filters + include).
- GET `cashboxes`, GET `cashboxes/{cashBoxId}/balance`.
- GET `transaction-types`, GET `payment-methods`, GET `funds`, GET `spending-items`, GET `counterparties`.
- Receipts: GET `receipts`, POST `receipts/contract`, POST `receipts/director-loan`.
- Spendings: GET `spendings`, POST `spendings`.
- Director withdrawal: POST `director-withdrawal`.
- Transfers: GET `cash-transfers`, POST `cash-transfers`.
- Back-compat list aliases: `/api/finances/transactions`, `/api/finances/receipts`, `/api/finances/spendings`.

Filtering/includes (lists)
- Common params: `page`, `per_page`, `sort`, `direction`, `date_from`, `date_to`, `search`.
- Cashbox filter accepts `cashbox_id`.
- `include` allows related data:
  - transactions: `cashbox`, `company`, `counterparty`, `contract`, `transactionType`, `paymentMethod`
  - receipts: `cashbox`, `company`, `counterparty`, `contract`, `transaction`
  - spendings: `cashbox`, `company`, `counterparty`, `contract`, `item`, `fund`, `transaction`, `spentToUser`

Requests/validation
- FormRequests under `App\Http\Requests\Finance\*` enforce `sum > 0`, cashbox existence, `from != to`, and date formats. Table is `legacy_new.cashboxes`.

Sequence (simplified)
1) Controller validates FormRequest and injects tenant/company/user ids.
2) Calls `FinanceService` method in a `legacy_new` transaction.
3) Service creates domain records, calls `completeTransaction`, writes `cashbox_history`, emits `FinancialActionLogged`.
4) DTO/Resource returned.

Sequence (transferBetweenCashBoxes)
```
Controller
 -> FormRequest.validate()
 -> FinanceService.transferBetweenCashBoxes()
     -> DB::connection('legacy_new')->transaction()
     -> assert sum>0, boxes exist, same tenant/company, from!=to
     -> lock cashbox rows
     -> create transaction_out
     -> completeTransaction (balance check, history)
     -> create transaction_in
     -> completeTransaction (balance check, history)
     -> create cash_transfer record
     -> emit FinancialActionLogged
 -> return DTO/Resource
```


## ?????
????????: `docs/finance/CASHBOXES.md`
