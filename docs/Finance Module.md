# Finance Module (current)

Назначение
- Финансовый контур: транзакции, приходы, расходы, переводы, кассы, справочники.
- Канон именования: `cashboxes` / `cashbox_id`.
- Все финансовые эффекты создаются через `FinanceService`.

Core tables (legacy_new)
- `transactions`, `receipts`, `spendings`, `cash_transfers`.
- `cashboxes`, `cashbox_company`, `cashbox_history`, `cashbox_balance_snapshots`.
- `transaction_types`, `payment_methods`, `spending_funds`, `spending_items`.
- `finance_allocations` — распределение платежей/расходов по договорам.

Services
- `App\Services\Finance\FinanceService` — бизнес-логика движения денег, история касс, проверки баланса.
- `TransactionService`, `ReceiptService`, `SpendingService`, `CashTransferService` — листинг/CRUD и фильтры.

DTO/Resources
- DTO: `ReceiptDTO`, `SpendingDTO`, `CashTransferDTO`, `TransactionDTO`.
- Resources: `ReceiptResource`, `SpendingResource`, `CashTransferResource`, `TransactionResource`.

API (prefix `/api/finance`)
- GET `transactions`, DELETE `transactions/{id}`.
- GET `cashboxes`, GET `cashboxes/{cashBoxId}/balance`.
- GET `transaction-types`, GET `payment-methods`, GET `funds`, GET `spending-items`, GET `counterparties`.
- GET `receipts`, POST `receipts/contract`, POST `receipts/director-loan`.
- GET `spendings`, POST `spendings`, DELETE `spendings/{id}`.
- POST `director-withdrawal`.
- GET/POST `cash-transfers`.
- Legacy aliases: `/api/finances/transactions|receipts|spendings`.

Filtering/includes
- Общие параметры: `page`, `per_page`, `sort`, `direction`, `date_from`, `date_to`, `search`.
- `include` раскрывает связи (см. IncludeRegistry и `docs/filterRules.txt`).

Sequence (simplified)
1) Controller валидирует FormRequest и подставляет tenant/company/user.
2) `FinanceService` выполняет операцию в транзакции `legacy_new`.
3) Пишет `cashbox_history`, создаёт доменные записи, эмитит события.

## REALITY STATUS
- Реально реализовано: FinanceService как single-writer, кассы и история, finance_allocations.
- Легаси: алиасы `/api/finances/*`, некоторые поля/таблицы наследованы из legacy.
- Не сделано: планировщик `CashBoxBalanceSnapshotJob` (снапшоты не пишутся без cron/queue).
