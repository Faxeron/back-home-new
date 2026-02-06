# KNOWN ISSUES (актуально)

- `PriceResolverService` не имеет fallback на `products`: при отсутствии строки в `product_company_prices` бросает исключение. Нужен полный backfill и контроль через `pricing:report-missing-company-prices`.
- `estimates.data` не обновляется текущим кодом и может содержать устаревший слепок (используется как fallback в анализе договора).
- `Transaction::scopeOnlyExpense` и `Spending::scopeOnlyExpense` фильтруют `sum < 0`, хотя канонический знак хранится в `transaction_types.sign`.
- Миграции `perenos_*` и `reload_spendings_*` не идемпотентны: повторный запуск может дублировать данные.
- `receipts.transaction_id` и `spendings.transaction_id` допускают NULL — возможны «сироты» при сбоях.
- `CashBoxBalanceSnapshotJob` не запланирован в `app/Console/Kernel.php`.
- Tenant-изоляция обеспечивается приложением; строгого разграничения на уровне БД нет.
- Dev-control флаги не описаны в документации.

## REALITY STATUS
- Реально реализовано: перечисленные проблемы подтверждаются текущим кодом/схемой.
- Легаси: `estimates.data` (слепок) и период миграции цен из `products` в `product_company_prices` (колонки операционных цен в `products` уже удалены миграцией `2026_02_06_000004_drop_operational_prices_from_products`).
- Не сделано: планировщик snapshots и общий контроль покрытия `product_company_prices`.
