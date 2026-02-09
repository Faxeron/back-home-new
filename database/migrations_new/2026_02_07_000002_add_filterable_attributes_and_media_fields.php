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

        $this->extendAttributeDefinitions($schema);
        $this->extendAttributeValues($schema);
        $this->extendProductMedia($schema);
    }

    public function down(): void
    {
        // Keep down() minimal; schema is forward-only in production.
    }

    private function extendAttributeDefinitions($schema): void
    {
        if (!$schema->hasTable('product_attribute_definitions')) {
            return;
        }

        $schema->table('product_attribute_definitions', function (Blueprint $table) use ($schema): void {
            if (!$schema->hasColumn('product_attribute_definitions', 'is_visible')) {
                $table->boolean('is_visible')->default(true)->after('value_type');
            }
            if (!$schema->hasColumn('product_attribute_definitions', 'is_filterable')) {
                $table->boolean('is_filterable')->default(false)->after('is_visible');
            }
            if (!$schema->hasColumn('product_attribute_definitions', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_filterable');
            }
            if (!$schema->hasColumn('product_attribute_definitions', 'unit')) {
                $table->string('unit', 50)->nullable()->after('sort_order');
            }
            if (!$schema->hasColumn('product_attribute_definitions', 'code')) {
                $table->string('code', 100)->nullable()->after('unit');
            }
        });

        $this->addIndex('product_attribute_definitions', 'prod_attr_defs_tenant_company_filterable_idx', ['tenant_id', 'company_id', 'is_filterable']);
        $this->addIndex('product_attribute_definitions', 'prod_attr_defs_tenant_company_sort_idx', ['tenant_id', 'company_id', 'sort_order']);
    }

    private function extendAttributeValues($schema): void
    {
        if (!$schema->hasTable('product_attribute_values')) {
            return;
        }

        // Replace old unique(product_id, attribute_id) with unique(tenant_id, company_id, product_id, attribute_id).
        $oldUnique = 'product_attr_values_product_attribute_unique';
        if ($this->indexExists('product_attribute_values', $oldUnique)) {
            $this->addIndex('product_attribute_values', 'prod_attr_values_product_id_idx', ['product_id']);
            DB::connection($this->connection)->statement("ALTER TABLE product_attribute_values DROP INDEX {$oldUnique}");
        }

        $this->addIndex('product_attribute_values', 'prod_attr_values_tenant_company_product_idx', ['tenant_id', 'company_id', 'product_id']);
        $this->addIndex('product_attribute_values', 'prod_attr_values_tenant_company_attribute_idx', ['tenant_id', 'company_id', 'attribute_id']);
        $this->addUnique('product_attribute_values', 'prod_attr_values_tenant_company_product_attr_unique', ['tenant_id', 'company_id', 'product_id', 'attribute_id']);
    }

    private function extendProductMedia($schema): void
    {
        if (!$schema->hasTable('product_media')) {
            return;
        }

        $schema->table('product_media', function (Blueprint $table) use ($schema): void {
            if (!$schema->hasColumn('product_media', 'alt')) {
                $table->string('alt', 255)->nullable()->after('url');
            }
            if (!$schema->hasColumn('product_media', 'is_main')) {
                $table->boolean('is_main')->default(false)->after('alt');
            }
        });

        $this->addIndex('product_media', 'product_media_tenant_company_product_idx', ['tenant_id', 'company_id', 'product_id']);
        $this->addIndex('product_media', 'product_media_tenant_company_product_sort_idx', ['tenant_id', 'company_id', 'product_id', 'sort_order']);
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndex(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $cols = implode(', ', $columns);
        DB::connection($this->connection)->statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$cols})");
    }

    /**
     * @param array<int, string> $columns
     */
    private function addUnique(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $cols = implode(', ', $columns);
        DB::connection($this->connection)->statement("ALTER TABLE {$table} ADD UNIQUE KEY {$indexName} ({$cols})");
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
