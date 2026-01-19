# Contract History

Endpoint
- `GET /api/contracts/{contract}/history`

Sources
- `contract_status_changes`
- `finance_audit_logs` filtered by `payload->contract_id`

Actions (finance_audit_logs)
- `contract.created` — contract created (draft)
- `contract.updated` — contract details updated
- `contract_receipt.created` — receipt added
- `spending.created` — spending added
- `spending.deleted` — spending deleted
- `contract_document.created` — document generated
- `contract_document.deleted` — document deleted

UI fields
- Date (`created_at`)
- Event title (`title`)
- User (`user.name` / `user.email`)

Deduplication
- History items are deduped by `created_at + title + user_id` before sorting.
