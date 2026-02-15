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
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('estimate_items')) {
            $schema->table('estimate_items', function (Blueprint $table) use ($schema): void {
                if (!$schema->hasColumn('estimate_items', 'qty_auto')) {
                    $table->decimal('qty_auto', 14, 2)->default(0)->after('qty');
                }
                if (!$schema->hasColumn('estimate_items', 'qty_manual')) {
                    $table->decimal('qty_manual', 14, 2)->default(0)->after('qty_auto');
                }
                if (!$schema->hasColumn('estimate_items', 'sort_order')) {
                    $table->integer('sort_order')->default(100)->after('group_id');
                }
            });

            if (!$this->indexExists('estimate_items', 'estimate_items_estimate_product_idx')) {
                $this->addIndex('estimate_items', 'estimate_items_estimate_product_idx', ['estimate_id', 'product_id']);
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank: no destructive changes on production data.
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $db = $connection->getDatabaseName();

        if ($driver === 'pgsql') {
            return $connection->table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();
        }

        return $connection->table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndex(string $table, string $indexName, array $columns): void
    {
        $cols = implode(', ', $columns);
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$cols})");

            return;
        }

        $connection->statement("CREATE INDEX {$indexName} ON {$table} ({$cols})");
    }
};
