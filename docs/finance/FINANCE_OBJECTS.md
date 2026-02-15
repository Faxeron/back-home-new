# Finance Objects Module

Purpose
- `finance_object` is the central accounting entity.
- Money movement is tracked through `transactions` as source-of-truth.
- `receipts` / `spendings` stay as UX/input projections.

Scope in current implementation
- Unified entity: `finance_objects`.
- `finance_objects.type` is stored as `varchar(32)` (not DB enum).
- Money binding:
  - direct: `transactions.finance_object_id`
  - split: `finance_object_allocations`
- Inbox for unassigned operations.
- Contract compatibility layer (dual-write with `contract_id`).

Type model
- Global catalog: `finance_object_types` (stable technical key + default metadata).
- Company overrides: `finance_object_type_settings` (`tenant_id`, `company_id`, `type_key`).
- UI type list is resolved as:
  - name: `COALESCE(settings.name_ru, types.default_name_ru)`
  - icon: `COALESCE(settings.icon, types.default_icon)`
  - sort: `COALESCE(settings.sort_order, types.default_sort_order)`
  - enabled: `settings.is_enabled`
- Disabled type behavior:
  - new objects of disabled type are blocked
  - existing objects remain visible
  - object card shows disabled-type marker.

MVP object types (enabled)
- `CONTRACT`
- `PROJECT`
- `EVENT`
- `ORDER`
- `SUBSCRIPTION`
- `TENDER`
- `SERVICE`
- Technical/system:
  - `INTERNAL`
  - `LEGACY_IMPORT`

Unified statuses
- `DRAFT`
- `ACTIVE`
- `ON_HOLD`
- `DONE`
- `CANCELED`
- `ARCHIVED`

Rules
- Assignment invariant (XOR):
  - either `finance_object_id`
  - or allocations
  - not both and not empty (hard-enforced in assignment service endpoints).
- Date rule:
  - `date_to >= date_from` when `date_to` is set.
- Money write restrictions:
  - `ARCHIVED`, `CANCELED` cannot accept new operations.
  - `DONE` accepts post-factum operations.

Backfill/migration strategy (implemented)
- `contracts` -> `finance_objects` (1:1, type `CONTRACT`).
- Backfill `transactions`, `receipts`, `spendings` by `contract_id`.
- Historical leftovers are mapped to a per-company `LEGACY_IMPORT` object.

API surface (new)
- `GET /api/finance-object-types`
- `PATCH /api/finance-object-types/{typeKey}/settings`
- `GET /api/finance/objects`
- `POST /api/finance/objects`
- `GET /api/finance/objects/lookup`
- `GET /api/finance/objects/{financeObject}`
- `PUT /api/finance/objects/{financeObject}`
- `GET /api/finance/objects/{financeObject}/transactions`
- `GET /api/finance/transactions/unassigned`
- `POST /api/finance/transactions/{transaction}/assign-object`
- `POST /api/finance/transactions/unassigned/bulk-assign`

Frontend surface (new)
- `operations/finance-objects` (list)
- `operations/finance-objects/[id]` (object card)
- `finance/unassigned` (inbox for unassigned operations)
- Finance object selector added to transactions/receipts/spendings filtering and entry flows.

## REALITY STATUS
- Реально реализовано:
  - DB schema (`finance_objects`, `finance_object_allocations`, links from core finance tables + contracts).
  - Backfill migration for contracts and historical records.
  - API endpoints for object CRUD, lookup, object transactions, unassigned inbox, assignment/bulk assignment.
  - Dual-write compatibility and object propagation in finance services.
  - Frontend pages + navigation + filters + dictionary support.
- Легаси:
  - `contract_id` remains active for compatibility in phase 1.
  - Existing finance screens still carry pre-existing type/UX debt.
- Не сделано:
  - Global hard-enforcement on all operation create/update paths (phase 3 target).
  - Automatic matching and advanced analytics phase.
