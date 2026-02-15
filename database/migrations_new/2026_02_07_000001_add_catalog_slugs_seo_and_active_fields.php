<?php

declare(strict_types=1);

use App\Services\Catalog\CatalogSlugService;
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

        $this->addCategoryColumns($schema);
        $this->addSubcategoryColumns($schema);
        $this->addBrandColumns($schema);

        // Backfill missing slugs before adding unique constraints.
        $this->backfillMissingSlugs();

        $this->addIndexesAndConstraints();
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        // Keep down() conservative: drop only what we added.
        if ($schema->hasTable('product_categories')) {
            $schema->table('product_categories', function (Blueprint $table) use ($schema): void {
                foreach (['slug', 'seo_title', 'seo_description', 'h1', 'is_active', 'sort_order'] as $col) {
                    if ($schema->hasColumn('product_categories', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if ($schema->hasTable('product_subcategories')) {
            $schema->table('product_subcategories', function (Blueprint $table) use ($schema): void {
                foreach (['slug', 'seo_title', 'seo_description', 'h1', 'is_active', 'sort_order'] as $col) {
                    if ($schema->hasColumn('product_subcategories', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if ($schema->hasTable('product_brands')) {
            $schema->table('product_brands', function (Blueprint $table) use ($schema): void {
                foreach (['slug', 'is_active', 'sort_order'] as $col) {
                    if ($schema->hasColumn('product_brands', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    private function addCategoryColumns($schema): void
    {
        if (!$schema->hasTable('product_categories')) {
            return;
        }

        $schema->table('product_categories', function (Blueprint $table) use ($schema): void {
            if (!$schema->hasColumn('product_categories', 'slug')) {
                $table->string('slug', 255)->nullable()->after('name');
            }
            if (!$schema->hasColumn('product_categories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('slug');
            }
            if (!$schema->hasColumn('product_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
            if (!$schema->hasColumn('product_categories', 'seo_title')) {
                $table->string('seo_title', 255)->nullable()->after('is_active');
            }
            if (!$schema->hasColumn('product_categories', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('seo_title');
            }
            if (!$schema->hasColumn('product_categories', 'h1')) {
                $table->string('h1', 255)->nullable()->after('seo_description');
            }
        });
    }

    private function addSubcategoryColumns($schema): void
    {
        if (!$schema->hasTable('product_subcategories')) {
            return;
        }

        $schema->table('product_subcategories', function (Blueprint $table) use ($schema): void {
            if (!$schema->hasColumn('product_subcategories', 'slug')) {
                $table->string('slug', 255)->nullable()->after('name');
            }
            if (!$schema->hasColumn('product_subcategories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('slug');
            }
            if (!$schema->hasColumn('product_subcategories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
            if (!$schema->hasColumn('product_subcategories', 'seo_title')) {
                $table->string('seo_title', 255)->nullable()->after('is_active');
            }
            if (!$schema->hasColumn('product_subcategories', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('seo_title');
            }
            if (!$schema->hasColumn('product_subcategories', 'h1')) {
                $table->string('h1', 255)->nullable()->after('seo_description');
            }
        });
    }

    private function addBrandColumns($schema): void
    {
        if (!$schema->hasTable('product_brands')) {
            return;
        }

        $schema->table('product_brands', function (Blueprint $table) use ($schema): void {
            if (!$schema->hasColumn('product_brands', 'slug')) {
                $table->string('slug', 255)->nullable()->after('name');
            }
            if (!$schema->hasColumn('product_brands', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('slug');
            }
            if (!$schema->hasColumn('product_brands', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });
    }

    private function backfillMissingSlugs(): void
    {
        $schema = Schema::connection($this->connection);
        $slugger = app(CatalogSlugService::class);

        foreach (['product_categories', 'product_subcategories', 'product_brands'] as $table) {
            if (!$schema->hasTable($table) || !$schema->hasColumn($table, 'slug')) {
                continue;
            }

            DB::connection($this->connection)
                ->table($table)
                ->select(['id', 'tenant_id', 'company_id', 'name', 'slug'])
                ->where(function ($q) {
                    $q->whereNull('slug')->orWhere('slug', '');
                })
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($slugger, $table): void {
                    foreach ($rows as $row) {
                        $tenantId = $row->tenant_id === null ? null : (int) $row->tenant_id;
                        $companyId = $row->company_id === null ? null : (int) $row->company_id;
                        $name = (string) ($row->name ?? '');

                        $slug = $slugger->uniqueForTable($this->connection, $table, $name, $tenantId, $companyId, (int) $row->id);

                        DB::connection($this->connection)
                            ->table($table)
                            ->where('id', $row->id)
                            ->update(['slug' => $slug]);
                    }
                });
        }

        // Make slug NOT NULL on MySQL-like drivers once populated.
        $driver = DB::connection($this->connection)->getDriverName();
        if ($driver === 'mysql') {
            foreach (['product_categories', 'product_subcategories', 'product_brands'] as $table) {
                if ($schema->hasTable($table) && $schema->hasColumn($table, 'slug')) {
                    DB::connection($this->connection)->statement("ALTER TABLE {$table} MODIFY slug VARCHAR(255) NOT NULL");
                }
            }
        }
    }

    private function addIndexesAndConstraints(): void
    {
        $schema = Schema::connection($this->connection);

        // product_categories
        if ($schema->hasTable('product_categories')) {
            $this->addIndex('product_categories', 'product_categories_tenant_company_active_idx', ['tenant_id', 'company_id', 'is_active']);
            $this->addIndex('product_categories', 'product_categories_tenant_company_sort_idx', ['tenant_id', 'company_id', 'sort_order']);
            $this->addUnique('product_categories', 'product_categories_tenant_company_slug_unique', ['tenant_id', 'company_id', 'slug']);
        }

        // product_subcategories
        if ($schema->hasTable('product_subcategories')) {
            $this->addIndex('product_subcategories', 'product_subcategories_tenant_company_category_idx', ['tenant_id', 'company_id', 'category_id']);
            $this->addIndex('product_subcategories', 'product_subcategories_tenant_company_active_idx', ['tenant_id', 'company_id', 'is_active']);
            $this->addUnique('product_subcategories', 'product_subcategories_tenant_company_slug_unique', ['tenant_id', 'company_id', 'slug']);
        }

        // product_brands
        if ($schema->hasTable('product_brands')) {
            $this->addIndex('product_brands', 'product_brands_tenant_company_active_idx', ['tenant_id', 'company_id', 'is_active']);
            $this->addUnique('product_brands', 'product_brands_tenant_company_slug_unique', ['tenant_id', 'company_id', 'slug']);
        }
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
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$cols})");

            return;
        }

        $connection->statement("CREATE INDEX {$indexName} ON {$table} ({$cols})");
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
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} ADD UNIQUE KEY {$indexName} ({$cols})");

            return;
        }

        $connection->statement("CREATE UNIQUE INDEX {$indexName} ON {$table} ({$cols})");
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
};
