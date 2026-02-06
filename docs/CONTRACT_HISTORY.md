# Contract History

Endpoint
- GET `/api/contracts/{contract}/history`

Sources
- `contract_status_changes`
- `finance_audit_logs` filtered by `payload->contract_id`

Actions (finance_audit_logs)
- `contract.created` Ч договор создан (черновик)
- `contract.updated` Ч обновлены данные договора
- `contract_receipt.created` Ч приход по договору
- `spending.created` Ч расход
- `spending.deleted` Ч удаление расхода
- `contract_document.created` Ч сформирован документ
- `contract_document.deleted` Ч удален документ

UI fields
- Date (`created_at`)
- Event title (`title`)
- User (`user.name` / `user.email`)

Deduplication
- »стори€ дедуплицируетс€ по `created_at + title + user_id`.

## REALITY STATUS
- –еально реализовано: aggregation из `contract_status_changes` и `finance_audit_logs`.
- Ћегаси: формат title дл€ некоторых legacy action может отличатьс€.
- Ќе сделано: расширенный аудит (например, изменени€ позиций договора).
