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
            !$schema->hasTable('receipts')
            || !$schema->hasTable('transactions')
            || !$schema->hasColumn('receipts', 'id')
            || !$schema->hasColumn('receipts', 'tenant_id')
            || !$schema->hasColumn('receipts', 'company_id')
            || !$schema->hasColumn('receipts', 'counterparty_id')
            || !$schema->hasColumn('receipts', 'payment_date')
            || !$schema->hasColumn('receipts', 'cashflow_item_id')
            || !$schema->hasColumn('receipts', 'transaction_id')
            || !$schema->hasColumn('transactions', 'id')
            || !$schema->hasColumn('transactions', 'cashflow_item_id')
            || !$schema->hasColumn('transactions', 'date_is_paid')
        ) {
            return;
        }

        $db = DB::connection($this->connection);

        $groups = $db->table('receipts')
            ->select('tenant_id', 'company_id', 'counterparty_id')
            ->whereNotNull('counterparty_id')
            ->groupBy('tenant_id', 'company_id', 'counterparty_id')
            ->get();

        $receiptIdsForCashflowOne = [];
        $receiptIdsForCashflowTwo = [];
        $transactionUpdates = [];

        foreach ($groups as $group) {
            $rows = $db->table('receipts')
                ->select('id', 'payment_date', 'transaction_id')
                ->where('tenant_id', $group->tenant_id)
                ->where('company_id', $group->company_id)
                ->where('counterparty_id', $group->counterparty_id)
                ->orderByRaw('CASE WHEN payment_date IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            foreach ($rows as $index => $row) {
                $cashflowItemId = $index === 0 ? 1 : 2;

                if ($cashflowItemId === 1) {
                    $receiptIdsForCashflowOne[] = (int) $row->id;
                } else {
                    $receiptIdsForCashflowTwo[] = (int) $row->id;
                }

                if (!empty($row->transaction_id)) {
                    $transactionUpdates[(int) $row->transaction_id] = [
                        'cashflow_item_id' => $cashflowItemId,
                        'date_is_paid' => $row->payment_date,
                    ];
                }
            }
        }

        foreach (array_chunk($receiptIdsForCashflowOne, 1000) as $chunk) {
            $db->table('receipts')
                ->whereIn('id', $chunk)
                ->update(['cashflow_item_id' => 1]);
        }

        foreach (array_chunk($receiptIdsForCashflowTwo, 1000) as $chunk) {
            $db->table('receipts')
                ->whereIn('id', $chunk)
                ->update(['cashflow_item_id' => 2]);
        }

        $transactionBatches = [];
        foreach ($transactionUpdates as $transactionId => $payload) {
            $date = $payload['date_is_paid'] !== null ? (string) $payload['date_is_paid'] : '__NULL__';
            $key = $payload['cashflow_item_id'] . '|' . $date;

            if (!isset($transactionBatches[$key])) {
                $transactionBatches[$key] = [
                    'cashflow_item_id' => $payload['cashflow_item_id'],
                    'date_is_paid' => $payload['date_is_paid'],
                    'ids' => [],
                ];
            }

            $transactionBatches[$key]['ids'][] = $transactionId;
        }

        foreach ($transactionBatches as $batch) {
            foreach (array_chunk($batch['ids'], 1000) as $chunk) {
                $db->table('transactions')
                    ->whereIn('id', $chunk)
                    ->update([
                        'cashflow_item_id' => $batch['cashflow_item_id'],
                        'date_is_paid' => $batch['date_is_paid'],
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Forward-only data migration.
    }
};
