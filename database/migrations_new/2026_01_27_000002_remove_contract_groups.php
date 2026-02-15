<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        if (!Schema::connection($this->connection)->hasTable('contract_groups')) {
            $this->dropContractGroupColumn();
            return;
        }

        $db = DB::connection($this->connection);

        if (Schema::connection($this->connection)->hasColumn('contracts', 'contract_group_id')) {
            $driver = $db->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $db->statement(<<<'SQL'
UPDATE contracts c
JOIN contract_groups g ON g.id = c.contract_group_id
SET
    c.address = COALESCE(c.address, g.site_address),
    c.contract_date = COALESCE(c.contract_date, g.contract_date),
    c.city_id = COALESCE(c.city_id, g.city_id),
    c.sale_type_id = COALESCE(c.sale_type_id, g.sale_type_id),
    c.total_amount = COALESCE(c.total_amount, g.total_amount),
    c.contract_status_id = COALESCE(c.contract_status_id, g.contract_status_id),
    c.estimate_id = COALESCE(c.estimate_id, g.estimate_id),
    c.work_done_date = COALESCE(c.work_done_date, g.work_date_actual),
    c.worker_id = COALESCE(c.worker_id, g.worker_id)
WHERE c.contract_group_id IS NOT NULL
SQL);
            } else {
                $db->statement(<<<'SQL'
UPDATE contracts c
SET
    address = COALESCE(c.address, g.site_address),
    contract_date = COALESCE(c.contract_date, g.contract_date),
    city_id = COALESCE(c.city_id, g.city_id),
    sale_type_id = COALESCE(c.sale_type_id, g.sale_type_id),
    total_amount = COALESCE(c.total_amount, g.total_amount),
    contract_status_id = COALESCE(c.contract_status_id, g.contract_status_id),
    estimate_id = COALESCE(c.estimate_id, g.estimate_id),
    work_done_date = COALESCE(c.work_done_date, g.work_date_actual),
    worker_id = COALESCE(c.worker_id, g.worker_id)
FROM contract_groups g
WHERE g.id = c.contract_group_id
  AND c.contract_group_id IS NOT NULL
SQL);
            }
        }

        $this->dropContractGroupColumn();

        Schema::connection($this->connection)->dropIfExists('contract_groups');
    }

    public function down(): void
    {
        // No automatic rollback. Use backups for production data.
    }

    private function dropContractGroupColumn(): void
    {
        if (!$this->hasColumn('contracts', 'contract_group_id')) {
            return;
        }

        if ($this->indexExistsOnColumn('contracts', 'contract_group_id')) {
            $this->dropIndex('contracts', 'contracts_contract_group_id_idx');
        }

        Schema::connection($this->connection)->table('contracts', function (Blueprint $table): void {
            $table->dropColumn('contract_group_id');
        });
    }

    private function hasColumn(string $table, string $column): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $db = $connection->getDatabaseName();
        $schema = $driver === 'pgsql' ? 'public' : $db;

        return $connection->table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }

    private function indexExistsOnColumn(string $table, string $column): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $db = $connection->getDatabaseName();

        if ($driver === 'pgsql') {
            return $connection->table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->whereRaw('indexdef ILIKE ?', ["%($column)%"])
                ->exists();
        }

        return $connection->table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }

    private function dropIndex(string $table, string $indexName): void
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} DROP INDEX {$indexName}");

            return;
        }

        $connection->statement("DROP INDEX IF EXISTS {$indexName}");
    }
};
