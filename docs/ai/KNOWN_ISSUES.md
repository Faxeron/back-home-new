# KNOWN ISSUES (актуально)

Комментарий (RU)
- Список основан на текущем коде и схеме `legacy_new`. Обновляй при правках миграций/сервисов.

- `FinanceService` уже работает по канону (`cashboxes`/`cashbox_id`); если валидация падает, проверь legacy-правила FormRequest.
- `TransactionService`, `ReceiptService`, `SpendingService` используют `DB::transaction()` без `legacy_new`, поэтому транзакции идут по default connection.
- Скоупы `Transaction::scopeOnlyExpense`/`Spending::scopeOnlyExpense` фильтруют `sum < 0`, хотя знак хранится в `transaction_types.sign`.
- Миграции `perenos_*` и `reload_spendings_*` не идемпотентны; повторный запуск может дублировать данные.
- `receipts.transaction_id` и `spendings.transaction_id` допускают NULL — возможны "сироты" при сбоях.
- Нет расписания для `CashBoxBalanceSnapshotJob`; без cron/queue снапшоты не пишутся.
- Возможны проблемы с кодировкой в справочниках (transaction_types/sale_types) при импорте из legacy.
- Dev-control флаги не документированы.
- Межтенантная изоляция обеспечивается приложением; на уровне БД нет строгого разграничения по tenant_id.
