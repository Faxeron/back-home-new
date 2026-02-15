<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('transactions')
            && $schema->hasColumn('transactions', 'company_id')
            && $schema->hasColumn('transactions', 'cashbox_id')
            && $schema->hasColumn('transactions', 'is_completed')
            && !$this->indexExists('transactions', 'transactions_company_cashbox_completed_idx')) {
            $this->addIndex('transactions', 'transactions_company_cashbox_completed_idx', ['company_id', 'cashbox_id', 'is_completed']);
        }

        if ($schema->hasTable('counterparties')
            && $schema->hasColumn('counterparties', 'company_id')
            && $schema->hasColumn('counterparties', 'phone_normalized')
            && !$this->indexExists('counterparties', 'counterparties_company_phone_idx')) {
            $this->addIndex('counterparties', 'counterparties_company_phone_idx', ['company_id', 'phone_normalized']);
        }

        if ($schema->hasTable('estimate_items')
            && $schema->hasColumn('estimate_items', 'estimate_id')
            && !$this->indexExists('estimate_items', 'estimate_items_estimate_id_idx')) {
            $this->addIndex('estimate_items', 'estimate_items_estimate_id_idx', ['estimate_id']);
        }
    }

    public function down(): void
    {
        if ($this->indexExists('transactions', 'transactions_company_cashbox_completed_idx')) {
            $this->dropIndex('transactions', 'transactions_company_cashbox_completed_idx');
        }

        if ($this->indexExists('counterparties', 'counterparties_company_phone_idx')) {
            $this->dropIndex('counterparties', 'counterparties_company_phone_idx');
        }

        if ($this->indexExists('estimate_items', 'estimate_items_estimate_id_idx')) {
            $this->dropIndex('estimate_items', 'estimate_items_estimate_id_idx');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $db = $connection->getDatabaseName();

        if ($driver === 'pgsql') {
            return $connection->table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        return $connection->table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndex(string $table, string $indexName, array $columns): void
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $cols = implode(', ', $columns);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$cols})");

            return;
        }

        $connection->statement("CREATE INDEX {$indexName} ON {$table} ({$cols})");
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
