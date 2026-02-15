# PostgreSQL Migration Runbook
REALITY STATUS: IMPLEMENTED (tooling), EXECUTION REQUIRES ENV-SPECIFIC VALIDATION

## Scope
- Snapshot-based migration from MySQL/MariaDB to PostgreSQL.
- Schema source of truth: Laravel migrations (`migrations_baseline` + `migrations_new`).
- Multi-tenant invariants: `tenant_id` and `company_id` must remain intact.

## Added tooling
- `scripts/migrate_mysql_to_pg.ps1`:
  - stages: `snapshot -> schema -> restore -> data -> verify`
  - creates dump + key counts
  - restores dump into temporary MySQL snapshot DB
  - runs `php artisan migrate:fresh` for PostgreSQL schema
  - runs `php artisan db:migrate-mysql-to-pg` for import + verification
  - runs `scripts/verify_counts.sql` via `psql`
  - writes logs and JSON artifacts in `storage/db-migration/<run_id>/`
- `app/Console/Commands/DbMigrateMysqlToPg.php`:
  - table discovery and FK-based ordering
  - chunked copy source->target
  - optional target truncate
  - sequence reset (`setval(max(id)+1)`)
  - counts/sums verification including 3-5 companies
- `scripts/verify_counts.sql`:
  - key-table counts
  - tenant/company null checks
  - sample companies and transaction sums/counts for selected period

## Typical run
```powershell
powershell -ExecutionPolicy Bypass -File scripts/migrate_mysql_to_pg.ps1 `
  -Stage all `
  -SourceHost 127.0.0.1 -SourcePort 3306 -SourceDatabase back_home_new -SourceUser root -SourcePassword "" `
  -PgHost 127.0.0.1 -PgPort 5432 -PgDatabase erp -PgUser postgres -PgPassword postgres `
  -Companies 5 -PeriodFrom 2026-01-01 -PeriodTo 2026-01-31
```

## Output artifacts
- `storage/db-migration/<run_id>/mysql_snapshot_*.sql`
- `storage/db-migration/<run_id>/mysql_snapshot_counts.tsv`
- `storage/db-migration/<run_id>/artisan_data_report.json`
- `storage/db-migration/<run_id>/artisan_verify_report.json`
- `storage/db-migration/<run_id>/verify_counts_psql.out`
- `storage/db-migration/<run_id>/migrate_mysql_to_pg.log`

