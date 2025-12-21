<?php

namespace App\Jobs;

use App\Domain\Finance\Models\CashBox;
use App\Services\Finance\FinanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CashBoxBalanceSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(FinanceService $financeService): void
    {
        $cashboxes = CashBox::query()->get();

        foreach ($cashboxes as $cashBox) {
            $balance = $financeService->getCashBoxBalance($cashBox->id);

            DB::connection('legacy_new')->table('cashbox_balance_snapshots')->insert([
                'tenant_id' => $cashBox->tenant_id,
                'company_id' => $cashBox->company_id,
                'cashbox_id' => $cashBox->id,
                'balance' => $balance,
                'calculated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
