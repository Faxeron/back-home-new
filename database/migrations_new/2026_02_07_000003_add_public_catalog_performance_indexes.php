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

        if ($schema->hasTable('products')) {
            $this->addIndexIfMissing(
                table: 'products',
                indexName: 'products_public_catalog_company_visibility_idx',
                columns: ['tenant_id', 'company_id', 'is_visible', 'archived_at', 'sort_order']
            );
        }

        if ($schema->hasTable('product_company_prices')) {
            $this->addIndexIfMissing(
                table: 'product_company_prices',
                indexName: 'prod_company_prices_active_lookup_idx',
                columns: ['tenant_id', 'company_id', 'product_id', 'is_active']
            );
        }
    }

    public function down(): void
    {
        // No-op.
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $cols = implode(', ', $columns);
        DB::connection($this->connection)->statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$cols})");
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};

