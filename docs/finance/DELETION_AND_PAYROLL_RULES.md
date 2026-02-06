# Правила удаления и ЗП

Документ фиксирует обязательные правила удаления финансовых данных и работу выплат ЗП.

## 1) Удаление транзакций
Доступ: admin/owner.

Как удаляем:
- API: `DELETE /api/finance/transactions/{id}`.
- Если транзакция связана с приходом/расходом, вызываются `FinanceService::deleteReceipt` или `FinanceService::deleteSpending`.
- Если связей нет — удаляется `cashbox_history`, затем сама транзакция.

## 2) Удаление договоров
Доступ: admin/owner.

Удаление запрещено, если есть:
- transactions
- receipts
- spendings
- finance_allocations

Если удаление разрешено, удаляются:
- `contract_documents` (включая файлы)
- `contract_items`
- `contract_status_changes`
- `finance_audit_logs` по payload->contract_id
- `payroll_accruals`
- `payroll_payout_items`
- `estimate.contract_id` -> NULL (если колонка есть)
- сам `contracts`

Тип удаления: жёсткое (DELETE из БД).

## 3) Правила выплат ЗП
Начисления
- Всегда привязаны к `contract_id` (и опционально к документу).
- Статусы: `active`, `paid`, `cancelled`.
- Поля: `amount`, `paid_amount`, `paid_at`.

Выплата (payout)
- Один payout на сотрудника и выбранные начисления.
- Создается один расход и одна транзакция на общую сумму.
- Для каждого начисления создаются `payroll_payout_items` и `finance_allocations` (kind = payroll).

Удаление выплаты
- Удаляется расход через `FinanceService`, затем связанные allocations и payout items.
- Откатываются `paid_amount` и `status` начислений.

## REALITY STATUS
- Реально реализовано: удаление транзакций/договоров по описанным правилам, payout flow.
- Легаси: возможны «сироты» при старых данных (nullable transaction_id).
- Не сделано: автоматический аудит несогласованностей и их восстановление.
