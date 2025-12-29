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

        if (!$schema->hasTable('product_attribute_definitions')) {
            return;
        }

        if ($schema->hasColumn('product_attribute_definitions', 'unit_id')) {
            if ($this->foreignExists('product_attribute_definitions', 'product_attr_defs_unit_id_fk')) {
                DB::connection($this->connection)->statement(
                    'ALTER TABLE product_attribute_definitions DROP FOREIGN KEY product_attr_defs_unit_id_fk'
                );
            }

            $schema->table('product_attribute_definitions', function (Blueprint $table): void {
                $table->dropColumn('unit_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('product_attribute_definitions')) {
            return;
        }

        if (!$schema->hasColumn('product_attribute_definitions', 'unit_id')) {
            $schema->table('product_attribute_definitions', function (Blueprint $table): void {
                $table->unsignedBigInteger('unit_id')->nullable()->after('value_type');
            });
        }

        if ($schema->hasColumn('product_attribute_definitions', 'unit_id')
            && !$this->foreignExists('product_attribute_definitions', 'product_attr_defs_unit_id_fk')) {
            $schema->table('product_attribute_definitions', function (Blueprint $table): void {
                $table->foreign('unit_id', 'product_attr_defs_unit_id_fk')
                    ->references('id')
                    ->on('product_units')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    private function foreignExists(string $table, string $constraint): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
    }
};
