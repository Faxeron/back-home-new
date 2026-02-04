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
            DB::connection($this->connection)
                ->statement('ALTER TABLE contracts DROP INDEX contracts_contract_group_id_idx');
        }

        Schema::connection($this->connection)->table('contracts', function (Blueprint $table): void {
            $table->dropColumn('contract_group_id');
        });
    }

    private function hasColumn(string $table, string $column): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.columns')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }

    private function indexExistsOnColumn(string $table, string $column): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }
};
