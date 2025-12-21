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

        if (!Schema::connection($this->connection)->hasTable('transactions')) {
            return;
        }

        // Drop FK if it exists to allow related_id to reference receipt/spending ids.
        $exists = DB::connection($this->connection)
            ->table('information_schema.KEY_COLUMN_USAGE')
            ->where('table_schema', DB::connection($this->connection)->getDatabaseName())
            ->where('table_name', 'transactions')
            ->where('column_name', 'related_id')
            ->where('constraint_name', $fkName)
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
