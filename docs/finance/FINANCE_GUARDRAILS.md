# FINANCE GUARDRAILS (P0)
See also: `docs/finance/DELETION_AND_PAYROLL_RULES.md`

Назначение
- Зафиксировать инварианты финансового контура.

Источник правды по балансу
- Баланс считается из `transactions.sum * transaction_types.sign` по завершенным транзакциям.
- Запрещено хранить balance в `cashboxes`.

Single Writer Rule
- Все финансовые эффекты создаются через `App\Services\Finance\FinanceService`.
- Запрещено писать `cashbox_history` из listeners/jobs/контроллеров.

Конкурентный доступ
- Операции с балансом сериализуются через row-level lock касс (FOR UPDATE).

Транзакции БД
- Все финансовые операции выполняются в транзакции `legacy_new`.

Снапшоты и история
- `cashbox_history` — журнал, не источник расчёта.
- Snapshots допустимы только как кеш для чтения.

## REALITY STATUS
- Реально реализовано: FinanceService как единственный писатель, lock касс, журнал истории.
- Легаси: часть старых listeners отключена, но код все еще присутствует.
- Не сделано: планировщик snapshot job и автоматические проверки расхождений.
