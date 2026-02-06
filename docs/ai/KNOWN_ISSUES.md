# KNOWN ISSUES (актуально)

- `PriceResolverService` не имеет fallback на `products`: при отсутствии строки в `product_company_prices` бросает исключение. Нужен полный backfill и контроль через `pricing:report-missing-company-prices`.
- Публичный API читает цены из `products`, а внутренний API — из `product_company_prices`: возможны расхождения до полного cutover.
- `estimates.data` не обновляется текущим кодом и может содержать устаревший слепок (используется как fallback в анализе договора).
- `Transaction::scopeOnlyExpense` и `Spending::scopeOnlyExpense` фильтруют `sum < 0`, хотя канонический знак хранится в `transaction_types.sign`.
- Миграции `perenos_*` и `reload_spendings_*` не идемпотентны: повторный запуск может дублировать данные.
- `receipts.transaction_id` и `spendings.transaction_id` допускают NULL — возможны «сироты» при сбоях.
- `CashBoxBalanceSnapshotJob` не запланирован в `app/Console/Kernel.php`.
- Tenant-изоляция обеспечивается приложением; строгого разграничения на уровне БД нет.
- Dev-control флаги не описаны в документации.

## REALITY STATUS
- Реально реализовано: перечисленные проблемы подтверждаются текущим кодом/схемой.
- Легаси: `estimates.data` и price-поля в `products`.
- Не сделано: планировщик snapshots и выравнивание pricing для Public API.
