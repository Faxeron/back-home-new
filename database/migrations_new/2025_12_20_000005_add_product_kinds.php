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

        if (!$schema->hasTable('product_kinds')) {
            $schema->create('product_kinds', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name', 255);
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->unique(['tenant_id', 'name'], 'product_kinds_tenant_name_unique');
            });
        }

        $count = DB::connection($this->connection)->table('product_kinds')->count();
        if ($count === 0) {
            $now = now();
            DB::connection($this->connection)->table('product_kinds')->insert([
                ['tenant_id' => 1, 'company_id' => 1, 'name' => 'септик', 'created_at' => $now, 'updated_at' => $now],
                ['tenant_id' => 1, 'company_id' => 1, 'name' => 'кессон', 'created_at' => $now, 'updated_at' => $now],
                ['tenant_id' => 1, 'company_id' => 1, 'name' => 'емкость', 'created_at' => $now, 'updated_at' => $now],
                ['tenant_id' => 1, 'company_id' => 1, 'name' => 'колодец', 'created_at' => $now, 'updated_at' => $now],
                ['tenant_id' => 1, 'company_id' => 1, 'name' => 'дренажный тоннель', 'created_at' => $now, 'updated_at' => $now],
                ['tenant_id' => 1, 'company_id' => 1, 'name' => 'компрессор', 'created_at' => $now, 'updated_at' => $now],
                ['tenant_id' => 1, 'company_id' => 1, 'name' => 'насос', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        $schema->table('products', function (Blueprint $table) use ($schema): void {
            if (!$schema->hasColumn('products', 'product_kind_id')) {
                $table->unsignedBigInteger('product_kind_id')->nullable()->after('product_type_id');
            }
        });

        if ($schema->hasColumn('products', 'product_kind_id') && !$this->foreignExists('products', 'products_product_kind_id_fk')) {
            $schema->table('products', function (Blueprint $table): void {
                $table->foreign('product_kind_id', 'products_product_kind_id_fk')
                    ->references('id')
                    ->on('product_kinds')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if ($schema->hasTable('product_attribute_definitions')
            && !$schema->hasColumn('product_attribute_definitions', 'product_kind_id')) {
            $schema->table('product_attribute_definitions', function (Blueprint $table) use ($schema): void {
                $table->unsignedBigInteger('product_kind_id')->nullable()->after('product_type_id');
            });
        }

        if ($schema->hasColumn('product_attribute_definitions', 'product_kind_id')
            && !$this->foreignExists('product_attribute_definitions', 'product_attr_defs_product_kind_id_fk')) {
            $schema->table('product_attribute_definitions', function (Blueprint $table): void {
                $table->foreign('product_kind_id', 'product_attr_defs_product_kind_id_fk')
                    ->references('id')
                    ->on('product_kinds')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('product_attribute_definitions')
            && $schema->hasColumn('product_attribute_definitions', 'product_kind_id')) {
            if ($this->foreignExists('product_attribute_definitions', 'product_attr_defs_product_kind_id_fk')) {
                $schema->table('product_attribute_definitions', function (Blueprint $table): void {
                    $table->dropForeign('product_attr_defs_product_kind_id_fk');
                });
            }
            $schema->table('product_attribute_definitions', function (Blueprint $table): void {
                $table->dropColumn('product_kind_id');
            });
        }

        if ($schema->hasTable('products') && $schema->hasColumn('products', 'product_kind_id')) {
            if ($this->foreignExists('products', 'products_product_kind_id_fk')) {
                $schema->table('products', function (Blueprint $table): void {
                    $table->dropForeign('products_product_kind_id_fk');
                });
            }
            $schema->table('products', function (Blueprint $table): void {
                $table->dropColumn('product_kind_id');
            });
        }

        $schema->dropIfExists('product_kinds');
    }

    private function foreignExists(string $table, string $constraint): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            return $connection->table('information_schema.table_constraints')
                ->where('table_schema', 'public')
                ->where('table_name', $table)
                ->where('constraint_name', $constraint)
                ->where('constraint_type', 'FOREIGN KEY')
                ->exists();
        }

        return $connection->table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $connection->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
    }
};
