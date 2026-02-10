<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillTransactionCashflowItems extends Command
{
    protected $signature = 'finance:backfill-cashflow
        {--tenant= : Only for a specific tenant_id}
        {--company= : Only for a specific company_id}
        {--chunk=200 : Chunk size}';

    protected $description = 'Backfill transactions.cashflow_item_id from spendings/receipts.';

    public function handle(): int
    {
        if (!Schema::connection('legacy_new')->hasTable('cashflow_items')) {
            $this->error('cashflow_items table not found.');
            return self::FAILURE;
        }

        $tenantId = $this->option('tenant');
        $tenantId = is_string($tenantId) && $tenantId !== '' ? (int) $tenantId : null;

        $companyId = $this->option('company');
        $companyId = is_string($companyId) && $companyId !== '' ? (int) $companyId : null;

        $chunk = (int) ($this->option('chunk') ?? 200);
        $chunk = $chunk > 0 ? $chunk : 200;

        $opInOther = $this->getCashflowItemId('OP_IN_OTHER');

        if (!$opInOther) {
            $this->error('Required cashflow item not found: OP_IN_OTHER.');
            return self::FAILURE;
        }

        $updatedSpendings = $this->backfillFromSpendings($tenantId, $companyId, $chunk);
        $updatedReceipts = $this->backfillFromReceipts($tenantId, $companyId, $chunk, $opInOther);

        $this->info("Updated transactions from spendings: {$updatedSpendings}");
        $this->info("Updated transactions from receipts: {$updatedReceipts}");

        return self::SUCCESS;
    }

    private function backfillFromSpendings(?int $tenantId, ?int $companyId, int $chunk): int
    {
        $query = DB::connection('legacy_new')
            ->table('spendings as s')
            ->join('spending_items as si', 'si.id', '=', 's.spending_item_id')
            ->select(['s.id', 's.transaction_id', 'si.cashflow_item_id'])
            ->whereNotNull('s.transaction_id')
            ->whereNotNull('si.cashflow_item_id');

        if ($tenantId !== null) {
            $query->where('s.tenant_id', $tenantId);
        }
        if ($companyId !== null) {
            $query->where('s.company_id', $companyId);
        }

        $updated = 0;

        // Use cursor instead of chunkById because of alias in select
        foreach ($query->orderBy('s.id')->cursor() as $row) {
            $affected = DB::connection('legacy_new')
                ->table('transactions')
                ->where('id', $row->transaction_id)
                ->where(function ($q) {
                    $q->whereNull('cashflow_item_id')->orWhere('cashflow_item_id', 0);
                })
                ->update(['cashflow_item_id' => $row->cashflow_item_id]);

            $updated += (int) $affected;
        }

        return $updated;
    }

    private function backfillFromReceipts(?int $tenantId, ?int $companyId, int $chunk, int $opInOther): int
    {
        $query = DB::connection('legacy_new')
            ->table('receipts')
            ->select(['id', 'transaction_id', 'contract_id'])
            ->whereNotNull('transaction_id');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $updated = 0;

        $query->orderBy('id')->chunkById($chunk, function ($rows) use (&$updated, $opInOther): void {
            foreach ($rows as $row) {
                $affected = DB::connection('legacy_new')
                    ->table('transactions')
                    ->where('id', $row->transaction_id)
                    ->where(function ($q) {
                        $q->whereNull('cashflow_item_id')->orWhere('cashflow_item_id', 0);
                    })
                    ->update(['cashflow_item_id' => $opInOther]);

                DB::connection('legacy_new')
                    ->table('receipts')
                    ->where('id', $row->id)
                    ->where(function ($q) {
                        $q->whereNull('cashflow_item_id')->orWhere('cashflow_item_id', 0);
                    })
                    ->update(['cashflow_item_id' => $opInOther]);

                $updated += (int) $affected;
            }
        });

        return $updated;
    }

    private function getCashflowItemId(string $code): ?int
    {
        return DB::connection('legacy_new')
            ->table('cashflow_items')
            ->where('code', $code)
            ->value('id');
    }
}
