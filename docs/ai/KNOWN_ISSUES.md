# KNOWN ISSUES (current pain points)

- Legacy DB connection `legacy_new` is required for migrations/seeds; missing config blocks local setup.
- Seeded names for `transaction_types` and `sale_types` are mojibake (encoding issue) and need re-seeding with UTF-8 labels.
- Finance columns were renamed `summ` -> `sum` by migration `2025_11_22_000002_multi_table_meta_updates`; verify code/DB consistency and re-run renames on any restored dumps.
- `transactions` still carries legacy `id_project` and `id_cash_box` naming; service layer uses new cashbox relations, so reconcile column usage and drop leftovers.
- Cashboxes have both `balance` column and history/snapshot tables; define single source of truth and backfill to avoid drift.
- Multi-tenant/user stamp columns are auto-added with default `tenant_id = 1`; enforcement/authorization by tenant is not implemented at DB level (risk of cross-tenant data mixing).
- Laravel system tables (`users`, `sessions`, `cache`, `jobs`, etc.) lack tenant/company columns; cross-tenant isolation depends entirely on app logic.
- Numerous data import migrations (`perenos_*`, `reload_spendings_*`) are not idempotent; re-running may duplicate or corrupt migrated finance data.
- Receipts/spendings use nullable `transaction_id`; orphaned financial rows are possible if transaction creation fails mid-flow.
- Contracts table retains legacy helper fields (`old_*`, worker/manager/measurer ids) with unclear current usage; UI/API should hide or clean them.
- `companies.code` is unique; legacy imports can collide if codes are not normalized before sync.
- Payment method/type seeds are hardcoded; any enum mismatch with frontend selects will break form submissions until synchronized.
- No documented scheduling for `CashBoxBalanceSnapshotJob`; snapshots may never run without manual cron setup.
- Dev-control endpoints exist but flags are undocumented; risk of toggling unknown behaviors in prod.
