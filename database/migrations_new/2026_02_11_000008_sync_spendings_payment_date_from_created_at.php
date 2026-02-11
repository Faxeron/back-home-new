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

        if (
            !$schema->hasTable('spendings')
            || !$schema->hasTable('transactions')
            || !$schema->hasColumn('spendings', 'payment_date')
            || !$schema->hasColumn('spendings', 'created_at')
            || !$schema->hasColumn('spendings', 'transaction_id')
            || !$schema->hasColumn('transactions', 'date_is_paid')
        ) {
            return;
        }

        $db = DB::connection($this->connection);
        $driver = $db->getDriverName();

        $dateExpr = match ($driver) {
            'pgsql' => 'CAST(created_at AS date)',
            'sqlite' => 'date(created_at)',
            default => 'DATE(created_at)',
        };

        // One-time mapping: spendings.payment_date = date(spendings.created_at)
        $db->table('spendings')
            ->whereNotNull('created_at')
            ->update([
                'payment_date' => DB::raw($dateExpr),
            ]);

        // Sync spendings.payment_date -> transactions.date_is_paid by transaction_id.
        if ($driver === 'pgsql') {
            $db->statement(
                'UPDATE transactions t
                 SET date_is_paid = s.payment_date
                 FROM spendings s
                 WHERE s.transaction_id = t.id'
            );

            return;
        }

        if ($driver === 'sqlite') {
            $db->statement(
                'UPDATE transactions
                 SET date_is_paid = (
                    SELECT s.payment_date
                    FROM spendings s
                    WHERE s.transaction_id = transactions.id
                    ORDER BY s.id DESC
                    LIMIT 1
                 )
                 WHERE id IN (
                    SELECT s.transaction_id
                    FROM spendings s
                    WHERE s.transaction_id IS NOT NULL
                 )'
            );

            return;
        }

        $db->statement(
            'UPDATE transactions t
             INNER JOIN spendings s ON s.transaction_id = t.id
             SET t.date_is_paid = s.payment_date'
        );
    }

    public function down(): void
    {
        // Forward-only data migration.
    }
};
