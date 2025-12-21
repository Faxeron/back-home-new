# KNOWN ISSUES (актуально)

Комментарий (RU)
- Список основан на текущем коде и схеме `legacy_new`. Обновляй при правках миграций/сервисов.

- Смешение `cash_boxes`/`cashboxes` и `cash_box_id`/`cashbox_id` в сервисах, FormRequest и тестах; после миграции `2025_12_19_000001` операции могут ломаться.
- `FinanceService` блокирует `cash_boxes` и пишет `cash_box_id`, а модели/схема используют `cashboxes`/`cashbox_id`.
- `TransactionService`, `ReceiptService`, `SpendingService` используют `DB::transaction()` без `legacy_new`, поэтому транзакции идут по default connection.
- Скоупы `Transaction::scopeOnlyExpense`/`Spending::scopeOnlyExpense` фильтруют `sum < 0`, хотя знак хранится в `transaction_types.sign`.
- Миграции `perenos_*` и `reload_spendings_*` не идемпотентны; повторный запуск может дублировать данные.
- `receipts.transaction_id` и `spendings.transaction_id` допускают NULL — возможны "сироты" при сбоях.
- Нет расписания для `CashBoxBalanceSnapshotJob`; без cron/queue снапшоты не пишутся.
- Возможны проблемы с кодировкой в справочниках (transaction_types/sale_types) при импорте из legacy.
- Dev-control флаги не документированы.
- Межтенантная изоляция обеспечивается приложением; на уровне БД нет строгого разграничения по tenant_id.
