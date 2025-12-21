# Finance Module (v1 draft)

- Core tables: `transactions` (sign via `transaction_types.sign`), `receipts`, `spendings`, `cash_transfers`, `cashbox_history`, `cash_boxes`, `payment_methods`, `transaction_types`.
- Transaction signs: `INCOME` +1, `DIRECTOR_LOAN` +1, `OUTCOME` -1, `DIRECTOR_WITHDRAWAL` -1, `TRANSFER_OUT` -1, `TRANSFER_IN` +1.
- Service entrypoint: `App\Services\Finance\FinanceService`:
  - `createContractReceipt` (requires contract), `createDirectorLoanReceipt`, `createSpending`, `createDirectorWithdrawal`, `transferBetweenCashBoxes`, `completeTransaction`, `getCashBoxBalance`.
  - Guards: sum>0, cashbox existence, from!=to, same tenant/company, insufficient funds prevention for negative-sign tx.
  - All wrapped in `DB::transaction`, logs `FinancialActionLogged` events, writes `cashbox_history`.
- DTOs: `ReceiptDTO`, `SpendingDTO`, `CashTransferDTO`, `TransactionDTO` returned from service methods.
- Snapshot: `CashBoxBalanceSnapshotJob` writes daily snapshots into `cashbox_balance_snapshots`.
- API (prefix `/api/finance`):
  - GET cashboxes + balance, GET balance by id.
  - Receipts: GET list, POST `/receipts/contract`, POST `/receipts/director-loan`.
  - Spendings: GET list, POST `/spendings`.
  - Director withdrawal: POST `/director-withdrawal`.
  - Transfers: GET/POST `/cash-transfers`.
- Requests/validation: FormRequests under `App\Http\Requests\Finance\*` enforce sum>0, ids exist, from!=to, dates.
- Resources: `ReceiptResource`, `SpendingResource`, `CashTransferResource`.
- Tests: `tests/Feature/FinanceServiceTest.php` cover transfers (2 tx + record, same-box forbidden), balance math, negative balance guard, director flows signs.

Sequence (simplified):
1) Controller validates FormRequest, enriches tenant/company/user ids.
2) Calls FinanceService method (DB::transaction).
3) Service creates domain records, runs `completeTransaction` -> marks paid/completed, writes `cashbox_history`, emits `FinancialActionLogged`.
4) Resource/JSON returned to client.

Sequence (transferBetweenCashBoxes):
```
Controller
 -> FormRequest.validate()
 -> FinanceService.transferBetweenCashBoxes()
     -> DB::transaction()
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
