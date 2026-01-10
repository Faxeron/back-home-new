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
        // drop existing FKs (cashbox_id and duplicates)
        $this->dropForeignIfExists('cash_transfers', 'cash_transfers_from_cashbox_id_fk');
        $this->dropForeignIfExists('cash_transfers', 'cash_transfers_to_cashbox_id_fk');
        $this->dropForeignIfExists('transactions', 'transactions_cashbox_id_fk');
        $this->dropForeignIfExists('receipts', 'receipts_cashbox_id_foreign');
        $this->dropForeignIfExists('receipts', 'receipts_cashbox_id_fk');
        $this->dropForeignIfExists('receipts', 'receipts_transaction_id_foreign'); // duplicate on transaction_id
        $this->dropForeignIfExists('receipts', 'receipts_transaction_id_fk'); // duplicate on transaction_id
        $this->dropForeignIfExists('spendings', 'spendings_cashbox_id_foreign');
        $this->dropForeignIfExists('cashbox_company', 'cashbox_company_cashbox_id_foreign');
        $this->dropForeignIfExists('cashbox_company', 'cashbox_company_company_id_foreign');
        $this->dropForeignIfExists('cashbox_company', 'cashbox_company_cashbox_id_fk');
        $this->dropForeignIfExists('cashbox_company', 'cashbox_company_company_id_fk');
        $this->dropForeignIfExists('cashbox_history', 'cashbox_history_cashbox_id_foreign');
        $this->dropForeignIfExists('cashbox_balance_snapshots', 'cashbox_balance_snapshots_cashbox_id_fk');

        // drop unused column
        if (Schema::connection($this->connection)->hasColumn('cash_transfers', 'created_by_user_id')) {
            DB::connection($this->connection)->statement('ALTER TABLE cash_transfers DROP COLUMN created_by_user_id');
        }

        // rebuild unique index on pivot
        DB::connection($this->connection)->statement('ALTER TABLE cashbox_company DROP INDEX cashbox_company_cashbox_id_company_id_unique');
        DB::connection($this->connection)->statement('ALTER TABLE cashbox_company ADD UNIQUE KEY cashbox_company_cashbox_id_company_id_unique (cashbox_id, company_id)');

        // add FK back to cashboxes
        if (! $this->foreignExists('transactions', 'transactions_cashbox_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE transactions ADD CONSTRAINT transactions_cashbox_id_fk FOREIGN KEY (cashbox_id) REFERENCES cashboxes(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }
        if (! $this->foreignExists('receipts', 'receipts_cashbox_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE receipts ADD CONSTRAINT receipts_cashbox_id_fk FOREIGN KEY (cashbox_id) REFERENCES cashboxes(id)');
        }
        if (! $this->foreignExists('spendings', 'spendings_cashbox_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE spendings ADD CONSTRAINT spendings_cashbox_id_fk FOREIGN KEY (cashbox_id) REFERENCES cashboxes(id)');
        }
        if (! $this->foreignExists('advances', 'advances_cashbox_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE advances ADD CONSTRAINT advances_cashbox_id_fk FOREIGN KEY (cashbox_id) REFERENCES cashboxes(id)');
        }
        if (! $this->foreignExists('cash_transfers', 'cash_transfers_from_cashbox_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE cash_transfers ADD CONSTRAINT cash_transfers_from_cashbox_id_fk FOREIGN KEY (from_cashbox_id) REFERENCES cashboxes(id) ON UPDATE CASCADE');
        }
        if (! $this->foreignExists('cash_transfers', 'cash_transfers_to_cashbox_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE cash_transfers ADD CONSTRAINT cash_transfers_to_cashbox_id_fk FOREIGN KEY (to_cashbox_id) REFERENCES cashboxes(id) ON UPDATE CASCADE');
        }
        if (! $this->foreignExists('cashbox_history', 'cashbox_history_cashbox_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE cashbox_history ADD CONSTRAINT cashbox_history_cashbox_id_fk FOREIGN KEY (cashbox_id) REFERENCES cashboxes(id) ON DELETE CASCADE');
        }
        if (! $this->foreignExists('cashbox_balance_snapshots', 'cashbox_balance_snapshots_cashbox_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE cashbox_balance_snapshots ADD CONSTRAINT cashbox_balance_snapshots_cashbox_id_fk FOREIGN KEY (cashbox_id) REFERENCES cashboxes(id) ON DELETE CASCADE');
        }
        if (! $this->indexExists('cashbox_balance_snapshots', 'cashbox_balance_snapshots_cashbox_calculated_idx')) {
            DB::connection($this->connection)->statement('ALTER TABLE cashbox_balance_snapshots ADD INDEX cashbox_balance_snapshots_cashbox_calculated_idx (cashbox_id, calculated_at)');
        }
        if (! $this->foreignExists('cashbox_company', 'cashbox_company_cashbox_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE cashbox_company ADD CONSTRAINT cashbox_company_cashbox_id_fk FOREIGN KEY (cashbox_id) REFERENCES cashboxes(id) ON DELETE CASCADE');
        }
        if (! $this->foreignExists('cashbox_company', 'cashbox_company_company_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE cashbox_company ADD CONSTRAINT cashbox_company_company_id_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE');
        }

        // fix extra FKs
        DB::connection($this->connection)->statement('UPDATE transactions t LEFT JOIN transactions r ON t.related_id = r.id SET t.related_id = NULL WHERE t.related_id IS NOT NULL AND r.id IS NULL');
        if (! $this->foreignExists('tenants', 'tenants_owner_user_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE tenants ADD CONSTRAINT tenants_owner_user_id_fk FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL');
        }
        if (! $this->foreignExists('transactions', 'transactions_related_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE transactions ADD CONSTRAINT transactions_related_id_fk FOREIGN KEY (related_id) REFERENCES transactions(id) ON DELETE SET NULL');
        }
        if (! $this->foreignExists('advances', 'advances_user_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE advances ADD CONSTRAINT advances_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id)');
        }
        if (! $this->foreignExists('advances', 'advances_transaction_id_fk')) {
            DB::connection($this->connection)->statement('ALTER TABLE advances ADD CONSTRAINT advances_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL');
        }
        DB::connection($this->connection)->statement('ALTER TABLE receipts MODIFY payment_date DATE NOT NULL DEFAULT (current_date())');

    }

    public function down(): void
    {
        // No automatic rollback (destructive rename). Restore from backup if needed.
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $db = DB::connection($this->connection);
        $database = $db->getDatabaseName();

        $exists = $db->table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();

        if ($exists) {
            $db->statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function foreignExists(string $table, string $constraint): bool
    {
        $db = DB::connection($this->connection);
        $database = $db->getDatabaseName();

        return $db->table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        $db = DB::connection($this->connection);
        $database = $db->getDatabaseName();

        return $db->table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }
};
