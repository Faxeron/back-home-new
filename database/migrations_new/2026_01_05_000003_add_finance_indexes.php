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

        if ($schema->hasTable('transactions')
            && $schema->hasColumn('transactions', 'company_id')
            && $schema->hasColumn('transactions', 'cashbox_id')
            && $schema->hasColumn('transactions', 'is_completed')
            && !$this->indexExists('transactions', 'transactions_company_cashbox_completed_idx')) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE transactions ADD INDEX transactions_company_cashbox_completed_idx (company_id, cashbox_id, is_completed)'
            );
        }

        if ($schema->hasTable('counterparties')
            && $schema->hasColumn('counterparties', 'company_id')
            && $schema->hasColumn('counterparties', 'phone_normalized')
            && !$this->indexExists('counterparties', 'counterparties_company_phone_idx')) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE counterparties ADD INDEX counterparties_company_phone_idx (company_id, phone_normalized)'
            );
        }

        if ($schema->hasTable('estimate_items')
            && $schema->hasColumn('estimate_items', 'estimate_id')
            && !$this->indexExists('estimate_items', 'estimate_items_estimate_id_idx')) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE estimate_items ADD INDEX estimate_items_estimate_id_idx (estimate_id)'
            );
        }
    }

    public function down(): void
    {
        if ($this->indexExists('transactions', 'transactions_company_cashbox_completed_idx')) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE transactions DROP INDEX transactions_company_cashbox_completed_idx'
            );
        }

        if ($this->indexExists('counterparties', 'counterparties_company_phone_idx')) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE counterparties DROP INDEX counterparties_company_phone_idx'
            );
        }

        if ($this->indexExists('estimate_items', 'estimate_items_estimate_id_idx')) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE estimate_items DROP INDEX estimate_items_estimate_id_idx'
            );
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }
};
