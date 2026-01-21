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
        if (!Schema::connection($this->connection)->hasTable('product_attribute_definitions')) {
            return;
        }

        DB::connection($this->connection)
            ->table('product_attribute_definitions')
            ->where('id', 4)
            ->update([
                'value_type' => 'number',
                'updated_at' => now(),
            ]);

        if (!Schema::connection($this->connection)->hasTable('product_attribute_values')) {
            return;
        }

        DB::connection($this->connection)
            ->table('product_attribute_values')
            ->where('attribute_id', 4)
            ->whereNull('value_number')
            ->whereNotNull('value_string')
            ->whereRaw("value_string REGEXP '^[0-9]+(\\\\.[0-9]+)?$'")
            ->update([
                'value_number' => DB::raw('CAST(value_string AS DECIMAL(14,4))'),
                'updated_at' => now(),
            ]);

        DB::connection($this->connection)
            ->table('product_attribute_values')
            ->where('attribute_id', 4)
            ->whereNull('value_number')
            ->whereNotNull('value_string')
            ->whereRaw("value_string REGEXP '^[0-9]+,[0-9]+$'")
            ->update([
                'value_number' => DB::raw("CAST(REPLACE(value_string, ',', '.') AS DECIMAL(14,4)) * 100"),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::connection($this->connection)->hasTable('product_attribute_definitions')) {
            return;
        }

        DB::connection($this->connection)
            ->table('product_attribute_definitions')
            ->where('id', 4)
            ->update([
                'value_type' => 'string',
                'updated_at' => now(),
            ]);

        if (!Schema::connection($this->connection)->hasTable('product_attribute_values')) {
            return;
        }

        DB::connection($this->connection)
            ->table('product_attribute_values')
            ->where('attribute_id', 4)
            ->whereNull('value_string')
            ->whereNotNull('value_number')
            ->update([
                'value_string' => DB::raw('CAST(value_number AS CHAR)'),
                'updated_at' => now(),
            ]);
    }
};
