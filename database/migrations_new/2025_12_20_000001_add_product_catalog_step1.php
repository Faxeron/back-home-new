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
        $this->createProductUnits();
        $this->addProductColumns();
        $this->createProductDescriptions();
        $this->createProductAttributes();
        $this->createProductMedia();
        $this->createProductRelations();
        $this->addProductIndexes();
    }

    public function down(): void
    {
        if ($this->indexExists('products', 'products_tenant_id_scu_unique')) {
            $this->dropIndex('products', 'products_tenant_id_scu_unique');
        }

        foreach (['product_type_id', 'category_id', 'sub_category_id', 'brand_id'] as $column) {
            $indexName = "products_{$column}_idx";
            if ($this->indexExists('products', $indexName)) {
                $this->dropIndex('products', $indexName);
            }
        }

        Schema::connection($this->connection)->table('products', function (Blueprint $table): void {
            if ($this->foreignExists('products', 'unit_id')) {
                $table->dropForeign('products_unit_id_fk');
            }
            if (Schema::connection($this->connection)->hasColumn('products', 'unit_id')) {
                $table->dropColumn('unit_id');
            }
            if (Schema::connection($this->connection)->hasColumn('products', 'contract_type_default')) {
                $table->dropColumn('contract_type_default');
            }
            if (Schema::connection($this->connection)->hasColumn('products', 'is_visible')) {
                $table->dropColumn('is_visible');
            }
            if (Schema::connection($this->connection)->hasColumn('products', 'is_top')) {
                $table->dropColumn('is_top');
            }
            if (Schema::connection($this->connection)->hasColumn('products', 'is_new')) {
                $table->dropColumn('is_new');
            }
        });

        Schema::connection($this->connection)->dropIfExists('product_relations');
        Schema::connection($this->connection)->dropIfExists('product_media');
        Schema::connection($this->connection)->dropIfExists('product_attribute_values');
        Schema::connection($this->connection)->dropIfExists('product_attribute_definitions');
        Schema::connection($this->connection)->dropIfExists('product_descriptions');
        Schema::connection($this->connection)->dropIfExists('product_units');
    }

    private function createProductUnits(): void
    {
        if (!Schema::connection($this->connection)->hasTable('product_units')) {
            Schema::connection($this->connection)->create('product_units', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 50);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $count = DB::connection($this->connection)->table('product_units')->count();
        if ($count === 0) {
            DB::connection($this->connection)->table('product_units')->insert([
                ['code' => 'SHT', 'name' => 'шт'],
                ['code' => 'M', 'name' => 'м'],
                ['code' => 'M2', 'name' => 'м2'],
                ['code' => 'HOUR', 'name' => 'час'],
                ['code' => 'ROLL', 'name' => 'рул'],
                ['code' => 'TRIP', 'name' => 'рейс'],
            ]);
        }
    }

    private function addProductColumns(): void
    {
        Schema::connection($this->connection)->table('products', function (Blueprint $table): void {
            if (!Schema::connection($this->connection)->hasColumn('products', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('product_type_id');
            }
            if (!Schema::connection($this->connection)->hasColumn('products', 'contract_type_default')) {
                $table->string('contract_type_default', 20)->nullable()->after('unit_id');
            }
            if (!Schema::connection($this->connection)->hasColumn('products', 'is_visible')) {
                $table->boolean('is_visible')->default(true)->after('contract_type_default');
            }
            if (!Schema::connection($this->connection)->hasColumn('products', 'is_top')) {
                $table->boolean('is_top')->default(false)->after('is_visible');
            }
            if (!Schema::connection($this->connection)->hasColumn('products', 'is_new')) {
                $table->boolean('is_new')->default(false)->after('is_top');
            }
        });

        if ($this->hasColumn('products', 'unit_id') && !$this->foreignExists('products', 'unit_id')) {
            Schema::connection($this->connection)->table('products', function (Blueprint $table): void {
                $table->foreign('unit_id', 'products_unit_id_fk')
                    ->references('id')
                    ->on('product_units')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    private function createProductDescriptions(): void
    {
        if (Schema::connection($this->connection)->hasTable('product_descriptions')) {
            return;
        }

        Schema::connection($this->connection)->create('product_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->text('description_short')->nullable();
            $table->longText('description_long')->nullable();
            $table->text('dignities')->nullable();
            $table->text('constructive')->nullable();
            $table->text('avito1')->nullable();
            $table->text('avito2')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('product_id', 'product_descriptions_product_id_unique');
            $table->foreign('product_id', 'product_descriptions_product_id_fk')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    private function createProductAttributes(): void
    {
        if (!Schema::connection($this->connection)->hasTable('product_attribute_definitions')) {
            Schema::connection($this->connection)->create('product_attribute_definitions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name', 255);
                $table->string('value_type', 20);
                $table->unsignedBigInteger('unit_id')->nullable();
                $table->unsignedBigInteger('product_type_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->foreign('unit_id', 'product_attr_defs_unit_id_fk')
                    ->references('id')
                    ->on('product_units')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->foreign('product_type_id', 'product_attr_defs_product_type_id_fk')
                    ->references('id')
                    ->on('product_types')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (!Schema::connection($this->connection)->hasTable('product_attribute_values')) {
            Schema::connection($this->connection)->create('product_attribute_values', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('attribute_id');
                $table->text('value_string')->nullable();
                $table->decimal('value_number', 14, 4)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->unique(['product_id', 'attribute_id'], 'product_attr_values_product_attribute_unique');
                $table->foreign('product_id', 'product_attr_values_product_id_fk')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreign('attribute_id', 'product_attr_values_attribute_id_fk')
                    ->references('id')
                    ->on('product_attribute_definitions')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    private function createProductMedia(): void
    {
        if (Schema::connection($this->connection)->hasTable('product_media')) {
            return;
        }

        Schema::connection($this->connection)->create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->string('type', 20);
            $table->text('url');
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['product_id'], 'product_media_product_id_idx');
            $table->foreign('product_id', 'product_media_product_id_fk')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    private function createProductRelations(): void
    {
        if (Schema::connection($this->connection)->hasTable('product_relations')) {
            return;
        }

        Schema::connection($this->connection)->create('product_relations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('related_product_id');
            $table->string('relation_type', 30);
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['product_id', 'related_product_id', 'relation_type'], 'product_relations_unique');
            $table->index(['product_id', 'relation_type'], 'product_relations_product_type_idx');
            $table->foreign('product_id', 'product_relations_product_id_fk')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('related_product_id', 'product_relations_related_product_id_fk')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    private function addProductIndexes(): void
    {
        if ($this->hasColumn('products', 'tenant_id') && $this->hasColumn('products', 'scu')) {
            if (!$this->indexExists('products', 'products_tenant_id_scu_unique')) {
                $this->addUniqueIndex('products', 'products_tenant_id_scu_unique', ['tenant_id', 'scu']);
            }
        }

        $indexColumns = ['product_type_id', 'category_id', 'sub_category_id', 'brand_id'];
        foreach ($indexColumns as $column) {
            if ($this->hasColumn('products', $column) && !$this->indexExistsOnColumn('products', $column)) {
                $indexName = "products_{$column}_idx";
                $this->addIndex('products', $indexName, [$column]);
            }
        }
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

    private function foreignExists(string $table, string $column): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $db = $connection->getDatabaseName();

        if ($driver === 'pgsql') {
            return $connection->table('information_schema.table_constraints as tc')
                ->join('information_schema.key_column_usage as kcu', function ($join): void {
                    $join->on('tc.constraint_name', '=', 'kcu.constraint_name')
                        ->on('tc.table_schema', '=', 'kcu.table_schema');
                })
                ->where('tc.table_schema', 'public')
                ->where('tc.table_name', $table)
                ->where('tc.constraint_type', 'FOREIGN KEY')
                ->where('kcu.column_name', $column)
                ->exists();
        }

        return $connection->table('information_schema.KEY_COLUMN_USAGE')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
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
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $cols = implode(', ', $columns);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$cols})");

            return;
        }

        $connection->statement("CREATE INDEX {$indexName} ON {$table} ({$cols})");
    }

    /**
     * @param array<int, string> $columns
     */
    private function addUniqueIndex(string $table, string $indexName, array $columns): void
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $cols = implode(', ', $columns);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} ADD UNIQUE KEY {$indexName} ({$cols})");

            return;
        }

        $connection->statement("CREATE UNIQUE INDEX {$indexName} ON {$table} ({$cols})");
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
