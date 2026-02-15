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
        $fkName = 'transactions_related_id_fk';
        $connection = DB::connection($this->connection);

        if (!Schema::connection($this->connection)->hasTable('transactions')) {
            return;
        }

        $schemaName = $connection->getDriverName() === 'pgsql'
            ? 'public'
            : $connection->getDatabaseName();

        // Drop FK if it exists to allow related_id to reference receipt/spending ids.
        $exists = $connection
            ->table('information_schema.table_constraints as tc')
            ->join('information_schema.key_column_usage as kcu', function ($join): void {
                $join->on('kcu.constraint_name', '=', 'tc.constraint_name')
                    ->on('kcu.table_schema', '=', 'tc.table_schema');
            })
            ->where('tc.constraint_type', 'FOREIGN KEY')
            ->where('tc.table_schema', $schemaName)
            ->where('kcu.table_schema', $schemaName)
            ->where('tc.table_name', 'transactions')
            ->where('kcu.column_name', 'related_id')
            ->where('tc.constraint_name', $fkName)
            ->exists();

        if ($exists) {
            Schema::connection($this->connection)->table('transactions', function ($table) use ($fkName): void {
                $table->dropForeign($fkName);
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty: keeping related_id without FK to allow linking receipts/spendings.
    }
};
