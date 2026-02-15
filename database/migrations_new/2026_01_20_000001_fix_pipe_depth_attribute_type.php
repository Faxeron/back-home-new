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
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (!Schema::connection($this->connection)->hasTable('product_attribute_definitions')) {
            return;
        }

        $connection
            ->table('product_attribute_definitions')
            ->where('id', 4)
            ->update([
                'value_type' => 'number',
                'updated_at' => now(),
            ]);

        if (!Schema::connection($this->connection)->hasTable('product_attribute_values')) {
            return;
        }

        $dotRegex = $driver === 'pgsql'
            ? "value_string ~ '^[0-9]+(\\\\.[0-9]+)?$'"
            : "value_string REGEXP '^[0-9]+(\\\\.[0-9]+)?$'";
        $commaRegex = $driver === 'pgsql'
            ? "value_string ~ '^[0-9]+,[0-9]+$'"
            : "value_string REGEXP '^[0-9]+,[0-9]+$'";

        $dotCast = $driver === 'pgsql'
            ? DB::raw('CAST(value_string AS NUMERIC(14,4))')
            : DB::raw('CAST(value_string AS DECIMAL(14,4))');
        $commaCast = $driver === 'pgsql'
            ? DB::raw("CAST(REPLACE(value_string, ',', '.') AS NUMERIC(14,4)) * 100")
            : DB::raw("CAST(REPLACE(value_string, ',', '.') AS DECIMAL(14,4)) * 100");

        $connection
            ->table('product_attribute_values')
            ->where('attribute_id', 4)
            ->whereNull('value_number')
            ->whereNotNull('value_string')
            ->whereRaw($dotRegex)
            ->update([
                'value_number' => $dotCast,
                'updated_at' => now(),
            ]);

        $connection
            ->table('product_attribute_values')
            ->where('attribute_id', 4)
            ->whereNull('value_number')
            ->whereNotNull('value_string')
            ->whereRaw($commaRegex)
            ->update([
                'value_number' => $commaCast,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (!Schema::connection($this->connection)->hasTable('product_attribute_definitions')) {
            return;
        }

        $connection
            ->table('product_attribute_definitions')
            ->where('id', 4)
            ->update([
                'value_type' => 'string',
                'updated_at' => now(),
            ]);

        if (!Schema::connection($this->connection)->hasTable('product_attribute_values')) {
            return;
        }

        $valueStringCast = $driver === 'pgsql'
            ? DB::raw('CAST(value_number AS TEXT)')
            : DB::raw('CAST(value_number AS CHAR)');

        $connection
            ->table('product_attribute_values')
            ->where('attribute_id', 4)
            ->whereNull('value_string')
            ->whereNotNull('value_number')
            ->update([
                'value_string' => $valueStringCast,
                'updated_at' => now(),
            ]);
    }
};
